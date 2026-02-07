<?php

use Illuminate\Http\Request;
use App\Database\StoredProcedureClient;

Route::post('/sp', function (Request $request) {

    $login  = (string) $request->input('login', '');
    $proc   = (string) $request->input('proc', '');
    $params = (array)  $request->input('params', []);

    // Allowlists
    $allowTenant = (array) config('stored_procedures.tenant', []);
    $allowGlobal = (array) config('stored_procedures.global', []);

    // Determine which allowlist the proc belongs to (no leaks)
    $scope = null; // 'tenant' | 'global'
    if (isset($allowTenant[$proc])) {
        $scope = 'tenant';
        $allow = $allowTenant;
    } elseif (isset($allowGlobal[$proc])) {
        $scope = 'global';
        $allow = $allowGlobal;
    } else {
        return response()->json(['rc'=>99,'ok'=>false,'error'=>'Invalid request.'], 400);
    }

    // Tenant is only required for tenant-scoped processes
    $tenant = null;
    if ($scope === 'tenant') {
        try {
            $tenant = \App\Support\Tenant::fromLogin($login);
        } catch (\Throwable $e) {
            // looks like failed login attempt
            return response()->json(['rc'=>99,'ok'=>false,'error'=>'Invalid credentials.'], 401);
        }
    }

    // Very first pass: positional params in correct count
    $expected = $allow[$proc]['params'] ?? [];
    if (count($params) !== count($expected)) {
        return response()->json(['rc'=>99,'ok'=>false,'error'=>'Invalid request.'], 400);
    }

    // Basic type enforcement (int/string) in declared order
    $typed = [];
    $i = 0;
    foreach ($expected as $name => $type) {
        $val = $params[$i] ?? null;

        if ($type === 'int') {
            if (!is_numeric($val)) {
                return response()->json(['rc'=>99,'ok'=>false,'error'=>'Invalid request.'], 400);
            }
            $typed[] = (int) $val;
        } elseif ($type === 'string') {
            $typed[] = (string) $val;
        } else {
            // unknown schema type -> treat as invalid request (don’t leak internals)
            return response()->json(['rc'=>99,'ok'=>false,'error'=>'Invalid request.'], 400);
        }
        $i++;
    }

    try {

        $sp = new StoredProcedureClient();

        $result = ($scope === 'global')
            ? $sp->execMasterWithReturnCode($proc, $typed)
            : $sp->execWithReturnCode($tenant, $proc, $typed);

    } catch (\Throwable $e) {

        // Log the REAL error for YOU — never for the caller
        logger()->error('Stored procedure execution failed', [
            'scope'  => $scope,
            'proc'   => $proc,
            'tenant' => $tenant ?? 'master',
            'error'  => $e->getMessage(),
        ]);

        // Make it look like a bad request — no hints to attackers
        return response()->json([
            'rc' => 99,
            'ok' => false,
            'error' => 'Invalid request.'
        ], 400);
    }

    return response()->json([
        'rc'    => $result['rc'],
        'ok'    => ($result['rc'] === 0),
        'data'  => $result['rows'],
        'error' => ($result['rc'] === 0) ? null : ['code' => $result['rc']],
    ]);
}
)->middleware('throttle:sp');

Route::fallback(function () {
    return response()->json([
        'rc' => 99,
        'ok' => false,
        'error' => 'Invalid request.',
    ], 404);
});

