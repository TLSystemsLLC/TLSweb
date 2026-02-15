<?php

use App\Database\StoredProcedureGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

Route::get('/', function () {
    \App\Support\TenantRegistry::allowedTenants();
    return view('auth.login');
});

Route::get('/login', function () {
    \App\Support\TenantRegistry::allowedTenants();
    return view('auth.login');
})->name('login');

Route::post('/login', function (Request $request, StoredProcedureGateway $gateway) {
    $login = (string) $request->input('login', '');
    $password = (string) $request->input('password', '');

    // Extract username (UserID) from login (tenant.username)
    $parts = explode('.', $login);
    $username = count($parts) > 1 ? implode('.', array_slice($parts, 1)) : $login;

    try {
        $result = $gateway->callWithFallback($login, 'spUser_Login', [$username, $password]);

        if ($result['rc'] === 0) {
            session(['user_login' => $login]);
            return response()->json(['rc' => 0, 'ok' => true]);
        }

        return response()->json(['rc' => $result['rc'], 'ok' => false, 'error' => 'Invalid credentials.'], 401);
    } catch (ServiceUnavailableHttpException $e) {
        throw $e;
    } catch (\Throwable $e) {
        // Log the actual error for debugging, but keep response generic
        logger()->error('Login failure', [
            'login' => $login,
            'error' => $e->getMessage()
        ]);
        return response()->json(['rc' => 99, 'ok' => false, 'error' => 'Invalid credentials.'], 401);
    }
});

Route::get('/dashboard', function () {
    if (!session()->has('user_login')) {
        return redirect()->route('login');
    }
    return view('dashboard');
})->name('dashboard');

Route::post('/logout', function () {
    session()->forget('user_login');
    return redirect()->route('login');
})->name('logout');
