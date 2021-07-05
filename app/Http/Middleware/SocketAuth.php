<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SocketAuth
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
        $user = auth()->user();
        $request->merge(['user' => $user ]);
        $request->setUserResolver(function () use ($user) {
            return $user;
        });
        return $next($request);
    }
}
