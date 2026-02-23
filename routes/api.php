<?php

use Illuminate\Http\Request;
use App\Database\StoredProcedureGateway;
use App\Database\Exceptions\InvalidCredentialsException;
use App\Database\Exceptions\InvalidRequestException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

Route::post('/sp', function (Request $request, StoredProcedureGateway $gateway) {
    // IMMEDIATE LOG: Before anything else
    logger()->error('CRITICAL: Route entry point reached');

    $login  = (string) $request->input('login', '');
    $proc   = (string) $request->input('proc', '');
    $params = (array)  $request->input('params', []);

    // 1. LOG INPUTS: Verify what Laravel sees
    logger()->error('CRITICAL: Request data', [
        'proc' => $proc,
        'login_sanitized' => $login !== '' ? substr($login, 0, 5) . '...' : 'empty'
    ]);

    // Correlation ID (returned only on true server errors)
    $cid = bin2hex(random_bytes(8)); // 16-char ID

    try {
        // Gateway handles: allowlist, scope, tenant parsing, param count, type enforcement
        $result = $gateway->callWithFallback($login !== '' ? $login : null, $proc, $params);

    } catch (\App\Database\Exceptions\InvalidCredentialsException $e) {
        // Force log using the fully qualified class name just in case of namespace issues
        logger()->error('AUTH FAILURE DETECTED', [
            'login' => $login,
            'message' => $e->getMessage()
        ]);

        return response()->json(['rc' => 99, 'ok' => false, 'error' => 'Invalid credentials.'], 401);

    } catch (\App\Database\Exceptions\InvalidRequestException $e) {
        logger()->error('INVALID REQUEST DETECTED', [
            'proc' => $proc,
            'message' => $e->getMessage()
        ]);

        return response()->json(['rc' => 99, 'ok' => false, 'error' => 'Invalid request.'], 400);

    } catch (\Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException $e) {
        // Re-throw to let the global handler in bootstrap/app.php catch it
        throw $e;

    } catch (\Throwable $e) {
        // CRITICAL: Log EVERYTHING that isn't a known success
        logger()->error('GENERAL EXCEPTION IN SP ROUTE', [
            'cid'       => $cid,
            'exception' => get_class($e),
            'message'   => $e->getMessage(),
            'file'      => $e->getFile(),
            'line'      => $e->getLine(),
            'proc'      => $proc,
            'login'     => $login
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

    // TEMPORARY PROD DEBUG: Log the RC and FIRST DATA ROW to see why failure looks like success
    $firstRow = $result['rows'][0] ?? null;
    logger()->error('DEBUG: SP execution result', [
        'rc' => $rc,
        'proc' => $proc,
        'has_rows' => !empty($result['rows']),
        'first_row_keys' => $firstRow ? array_keys($firstRow) : [],
        // Sanitize: only log values for specific non-PII keys if they exist (like 'Success' or 'Error')
        'status_hint' => $firstRow['Success'] ?? $firstRow['Error'] ?? $firstRow['Message'] ?? 'none'
    ]);

    if ($rc !== 0) {
        // Log business failure (e.g., login failed inside SP)
        logger()->error('SP BUSINESS FAILURE', [
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
