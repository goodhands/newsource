<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AcceptApiKeyFromQuery
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if ($request->has('api-key') || $request->query->has('apiKey')) {
            $token = $request->query('api-key') ?? $request->query('apiKey');
            $request->headers->set('Authorization', 'Bearer ' . $token);
        }

        return $next($request);
    }
}
