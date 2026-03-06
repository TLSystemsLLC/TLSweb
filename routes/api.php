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
        // Gateway handles: allowlist, scope, tenant parsing, param count, type enforcement
        $result = $gateway->callWithFallback($login !== '' ? $login : null, $proc, $params);

    } catch (InvalidCredentialsException $e) {
        // Log sanitized login failure (for monitoring password spraying etc.)
        logger()->warning('Invalid credentials attempt', [
            'login_hash' => $login !== '' ? substr(hash('sha256', $login), 0, 12) : null,
        ]);

        // Looks like failed login; do not leak why
        return response()->json(['rc' => 99, 'ok' => false, 'error' => 'Invalid credentials.'], 401);

    } catch (InvalidRequestException $e) {
        // Log invalid request (sanitized)
        logger()->notice('Invalid SP request', [
            'proc_hash' => $proc !== '' ? substr(hash('sha256', $proc), 0, 12) : null,
        ]);

        // Invalid proc/params/scope/etc. (no details leaked)
        return response()->json(['rc' => 99, 'ok' => false, 'error' => 'Invalid request.'], 400);

    } catch (ServiceUnavailableHttpException $e) {
        // Re-throw to let the global handler in bootstrap/app.php catch it
        throw $e;

    } catch (\Throwable $e) {
        // Log real error for you; do not leak tenant/proc/sql details to caller
        $errorMsg = $e->getMessage();
        $isMissingProc = str_contains($errorMsg, '2812') || str_contains($errorMsg, 'Could not find stored procedure');

        logger()->error('SP execution failed', [
            'cid'       => $cid,
            'exception' => get_class($e),
            'error'     => $errorMsg,
            // Hash the login string (not tenant) to correlate repeated attacks without disclosure
            'login_hash' => $login !== ''
                ? substr(hash('sha256', $login), 0, 12)
                : null,
            'missing_proc' => $isMissingProc ? $proc : null,
        ]);

        // True server-side failure: return 500 with CID
        // If it's a missing procedure, we return rc 99 but add a hint if in local environment
        $data = [
            'rc'    => 99,
            'ok'    => false,
            'error' => 'Server error.',
            'cid'   => $cid,
        ];

        if ($isMissingProc && app()->environment('local')) {
            $data['error'] = "Stored procedure [{$proc}] not found in database.";
        }

        return response()->json($data, 500);
    }

    // Stored procedure rc is a “business result”, not an exception.
    $rc = (int) ($result['rc'] ?? 99);

    if ($rc !== 0) {
        // Log business failure (e.g., login failed inside SP)
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
