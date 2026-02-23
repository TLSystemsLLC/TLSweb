<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ForceJson
{
    public function handle(Request $request, Closure $next)
    {
        // IMMEDIATE LOG: Before any processing
        logger()->error('MIDDLEWARE ENTRY: ForceJson', [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'ip' => $request->ip()
        ]);

        // Force JSON content negotiation for API endpoints
        $request->headers->set('Accept', 'application/json');

        return $next($request);
    }
}
