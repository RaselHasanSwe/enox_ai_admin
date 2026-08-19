<?php

namespace App\Http\Middleware;

use App\Support\EnoxSession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnoxAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! EnoxSession::isAuthenticated()) {
            return redirect()->route('login');
        }

        return $next($request);
    }
}
