<?php

use App\Database\StoredProcedureGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

Route::get('/', function () {
    return view('home');
});

Route::post('/contact', function (Request $request, StoredProcedureGateway $gateway) {
    // 1. Honeypot check
    if ($request->filled('website')) {
        return response()->json(['rc' => 0, 'ok' => true]);
    }

    // 2. Validate shape (UX only, per AI_RULES.md)
    $data = $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email|max:255',
        'phone' => 'nullable|string|max:50',
        'message' => 'required|string',
    ]);

    try {
        // 3. Call Stored Procedure via Gateway
        // This is a global procedure.
        $result = $gateway->call(null, 'spContactRequest_Save', [
            $data['name'],
            $data['email'],
            $data['phone'] ?? '',
            $data['message']
        ]);

        if ($result['rc'] === 0) {
            return response()->json([
                'rc' => 0,
                'ok' => true,
                'message' => 'Thank you! Your message has been sent.'
            ]);
        }

        return response()->json([
            'rc' => $result['rc'],
            'ok' => false,
            'error' => 'Business rule violation.'
        ], 422);

    } catch (\Throwable $e) {
        logger()->error('Contact form submission failed', [
            'error' => $e->getMessage()
        ]);

        return response()->json([
            'rc' => 99,
            'ok' => false,
            'error' => 'Unable to send message at this time.'
        ], 500);
    }
})->middleware('throttle:5,1');

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

Route::get('/dashboard/page/{key}', function ($key) {
    if (!session()->has('user_login')) {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    $view = "dashboard.pages.{$key}";

    if (!view()->exists($view)) {
        return response()->json(['error' => 'Page not found'], 404);
    }

    return view($view);
});

Route::post('/logout', function () {
    session()->forget('user_login');
    return redirect()->route('login');
})->name('logout');
