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

    // Sanitized correlation values (no PII)
    $loginHash = $login !== '' ? substr(hash('sha256', $login), 0, 12) : null;
    $procHash  = substr(hash('sha256', 'spUser_Login'), 0, 12);

    // Inspect ALL headers to see what is arriving from the firewall
    logger()->notice('WEB login headers raw', $request->headers->all());

    // Inspect common proxy IP headers to diagnose firewall forwarding
    $ipHeaders = [
        'ip' => $request->ip(),
        'X-Forwarded-For'   => $request->header('X-Forwarded-For'),
        'X-Real-IP'         => $request->header('X-Real-IP'),
        'True-Client-IP'    => $request->header('True-Client-IP'),
        'CF-Connecting-IP'  => $request->header('CF-Connecting-IP'),
        'X-Client-IP'       => $request->header('X-Client-IP'),
        'Forwarded'         => $request->header('Forwarded'),
        'X-Forwarded-Proto' => $request->header('X-Forwarded-Proto'),
        'X-Forwarded-Host'  => $request->header('X-Forwarded-Host'),
        'X-Forwarded-Port'  => $request->header('X-Forwarded-Port'),
    ];
    logger()->notice('WEB login headers', $ipHeaders);

    // Record the attempt (sanitized)
    logger()->notice('WEB login attempt', [
        'proc_hash' => $procHash,
        'login_hash' => $loginHash,
        'ip' => $request->ip(),
    ]);

    try {
        $result = $gateway->callWithFallback($login, 'spUser_Login', [$username, $password]);
        $rc = (int) ($result['rc'] ?? 99);

        if ($rc === 0) {
            // Success logging (sanitized)
            logger()->info('WEB login success', [
                'proc_hash' => $procHash,
                'login_hash' => $loginHash,
                'ip' => $request->ip(),
            ]);
            session(['user_login' => $login]);
            return response()->json(['rc' => 0, 'ok' => true]);
        }

        // Failure logging (sanitized)
        logger()->warning('WEB login failure', [
            'proc_hash' => $procHash,
            'login_hash' => $loginHash,
            'rc' => $rc,
            'ip' => $request->ip(),
        ]);
        return response()->json(['rc' => $rc, 'ok' => false, 'error' => 'Invalid credentials.'], 401);

    } catch (\App\Database\Exceptions\InvalidCredentialsException $e) {
        // This handles cases where the tenant is invalid or credentials format is bad
        logger()->warning('WEB login failure (invalid credentials)', [
            'proc_hash' => $procHash,
            'login_hash' => $loginHash,
            'ip' => $request->ip(),
            'message' => $e->getMessage()
        ]);
        return response()->json(['rc' => 99, 'ok' => false, 'error' => 'Invalid credentials.'], 401);

    } catch (ServiceUnavailableHttpException $e) {
        throw $e;
    } catch (\Throwable $e) {
        // Log the actual error for debugging (sanitized), keep response generic
        logger()->error('WEB login exception', [
            'proc_hash' => $procHash,
            'login_hash' => $loginHash,
            'ip' => $request->ip(),
            'exception' => get_class($e),
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
