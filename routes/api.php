<?php

use Illuminate\Http\Request;
use App\Database\StoredProcedureGateway;
use App\Database\Exceptions\InvalidCredentialsException;
use App\Database\Exceptions\InvalidRequestException;

Route::middleware(['throttle:30,1'])->post('/sp', function (Request $request, StoredProcedureGateway $gateway) {
    $login  = (string) $request->input('login', '');
    $proc   = (string) $request->input('proc', '');
    $params = (array)  $request->input('params', []);

    try {
        // Gateway handles: allowlist, scope, tenant parsing, param count, type enforcement
        $result = $gateway->call($login !== '' ? $login : null, $proc, $params);

    } catch (InvalidCredentialsException) {
        // Looks like failed login; do not leak why
        return response()->json(['rc'=>99,'ok'=>false,'error'=>'Invalid credentials.'], 401);

    } catch (InvalidRequestException) {
        return response()->json(['rc'=>99,'ok'=>false,'error'=>'Invalid request.'], 400);

    } catch (\Throwable $e) {
        // Log useful debugging info WITHOUT tenant/proc leakage concerns
        logger()->error('SP execution failed', [
            'has_login' => ($login !== ''),
            // We do NOT log proc or tenant here; you can add them back if you want,
            // but you previously cared about not leaking tenant names.
            'error' => $e->getMessage(),
            'exception' => get_class($e),
        ]);

        // Caller gets nothing actionable
        return response()->json(['rc'=>99,'ok'=>false,'error'=>'Invalid request.'], 400);
    }

    // Stored procedure rc is a “business result”, not an exception.
    $rc = (int) $result['rc'];

    return response()->json([
        'rc'    => $rc,
        'ok'    => ($rc === 0),
        'data'  => $result['rows'],
        'error' => ($rc === 0) ? null : ['code' => $rc],
    ], ($rc === 0) ? 200 : 422);

});

Route::fallback(function () {
    return response()->json([
        'rc' => 99,
        'ok' => false,
        'error' => 'Invalid request.',
    ], 400);
});
