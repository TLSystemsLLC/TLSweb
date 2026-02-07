<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

use App\Database\StoredProcedureClient;
use App\Support\Tenant;

Route::get('/sp-test2', function (\Illuminate\Http\Request $request) {
    try {
        $login  = (string) $request->query('login', '');
        $tenant = Tenant::fromLogin($login);
    } catch (\Throwable $e) {
        // Indistinguishable from a bad login attempt
        return response()->json([
            'rc'    => 99,
            'ok'    => false,
            'error' => 'Invalid credentials.',
        ], 401);
    }

    $sp = new StoredProcedureClient();
    $result = $sp->execWithReturnCode($tenant, 'spCompany_Get', [1]);

    return response()->json([
        'rc'    => $result['rc'],
        'ok'    => ($result['rc'] === 0),
        'data'  => $result['rows'],
        'error' => ($result['rc'] === 0) ? null : ['code' => $result['rc']],
    ]);
});
