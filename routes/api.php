<?php

use Illuminate\Http\Request;
use App\Database\StoredProcedureGateway;
use App\Database\Exceptions\InvalidCredentialsException;
use App\Database\Exceptions\InvalidRequestException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

Route::middleware(['throttle:30,1'])->post('/sp', function (Request $request, StoredProcedureGateway $gateway) {
    $login  = (string) $request->input('login', '');
    $proc   = (string) $request->input('proc', '');
    $params = (array)  $request->input('params', []);

    // Correlation ID (returned only on true server errors)
    $cid = bin2hex(random_bytes(8)); // 16-char ID

    try {
        // ALWAYS log exactly what came in (for debugging log issues)
        logger()->notice('Incoming SP request', [
            'proc_hash' => $proc !== '' ? substr(hash('sha256', $proc), 0, 12) : null,
            'login_hash' => $login !== '' ? substr(hash('sha256', $login), 0, 12) : null,
            // never log raw params for security
            'params_count' => count($params)
        ]);

        // Explicitly log login attempts to ensure visibility regardless of SP behavior
        if ($proc === 'spUser_Login') {
            logger()->notice('Login attempt received', [
                'login_hash' => $login !== '' ? substr(hash('sha256', $login), 0, 12) : null,
            ]);
        }

        // Gateway handles: allowlist, scope, tenant parsing, param count, type enforcement
        $result = $gateway->callWithFallback($login !== '' ? $login : null, $proc, $params);

    } catch (InvalidCredentialsException $e) {
        // Log sanitized login failure (for monitoring password spraying etc.)
        logger()->warning('Invalid credentials attempt', [
            'login_hash' => $login !== '' ? substr(hash('sha256', $login), 0, 12) : null,
            'message'    => $e->getMessage(),
        ]);

        // Looks like failed login; do not leak why
        return response()->json(['rc' => 99, 'ok' => false, 'error' => 'Invalid credentials.'], 401);

    } catch (InvalidRequestException $e) {
        // Log invalid request (sanitized)
        logger()->notice('Invalid SP request', [
            'proc_hash' => $proc !== '' ? substr(hash('sha256', $proc), 0, 12) : null,
            'message'   => $e->getMessage(),
        ]);

        // Invalid proc/params/scope/etc. (no details leaked)
        return response()->json(['rc' => 99, 'ok' => false, 'error' => 'Invalid request.'], 400);

    } catch (ServiceUnavailableHttpException $e) {
        // Re-throw to let the global handler in bootstrap/app.php catch it
        throw $e;

    } catch (\Throwable $e) {
        // Log real error for you; do not leak tenant/proc/sql details to caller
        logger()->error('SP execution failed', [
            'cid'       => $cid,
            'exception' => get_class($e),
            'error'     => $e->getMessage(),
            // Hash the login string (not tenant) to correlate repeated attacks without disclosure
            'login_hash' => $login !== ''
                ? substr(hash('sha256', $login), 0, 12)
                : null,
        ]);

        // True server-side failure: return 500 with CID
        return response()->json([
            'rc'    => 99,
            'ok'    => false,
            'error' => 'Server error.',
            'cid'   => $cid,
        ], 500);
    }

    // Stored procedure rc is a “business result”, not an exception.
    $rc = (int) ($result['rc'] ?? 99);

    // Always log the RC we received at NOTICE to ensure visibility in production
    logger()->notice('SP result received', [
        'rc' => $rc,
        'proc_hash' => $proc !== '' ? substr(hash('sha256', $proc), 0, 12) : null,
        'data_row_count' => count($result['rows'] ?? []),
    ]);

    if ($rc !== 0) {
        // Log business failure (e.g., login failed inside SP)
        // Elevated to WARNING to ensure visibility even when LOG_LEVEL is set to warning or higher.
        logger()->warning('SP business failure', [
            'rc' => $rc,
            'proc_hash' => $proc !== '' ? substr(hash('sha256', $proc), 0, 12) : null,
            'login_hash' => $login !== '' ? substr(hash('sha256', $login), 0, 12) : null,
        ]);
    } else {
        // Log success (for monitoring resource usage/valid logins)
        logger()->info('SP success', [
            'proc_hash' => $proc !== '' ? substr(hash('sha256', $proc), 0, 12) : null,
            'login_hash' => $login !== '' ? substr(hash('sha256', $login), 0, 12) : null,
        ]);

        // LOGGING-ONLY WORKAROUND: If login success is reported but no user rows returned, log it as a potential auth failure.
        // We use a specific message to differentiate from generic success.
        $isLogin = ($proc === 'spUser_Login');
        $hasRows = !empty($result['rows'] ?? []);

        if ($isLogin && !$hasRows) {
            logger()->warning('LOGIN FAILURE: Correct credentials format but no user record found', [
                'login_hash' => $login !== '' ? substr(hash('sha256', $login), 0, 12) : null,
                'rc' => $rc
            ]);
        }
    }

    return response()->json([
        'rc'    => $rc,
        'ok'    => ($rc === 0),
        'data'  => $result['rows'] ?? [],
        'error' => ($rc === 0) ? null : ['code' => $rc],
    ], ($rc === 0) ? 200 : 422);
});

Route::fallback(function () {
    return response()->json([
        'rc'    => 99,
        'ok'    => false,
        'error' => 'Invalid request.',
    ], 400);
});
