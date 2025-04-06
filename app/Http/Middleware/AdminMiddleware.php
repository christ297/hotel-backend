<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminMiddleware
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
        // Vérifier si l'utilisateur est authentifié et s'il est admin
        if (!Auth::check() && !Auth::user()->is_admin) {
            return response()->json(['message' => 'Accès interdit, vous n\'êtes pas administrateur.'], 403);
        }

        return $next($request);
    }
}
