<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$user->profile || !$user->profile->role) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses.',
            ], 403);
        }
        if ($user->profile->role->code !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Akses hanya diperbolehkan untuk admin.',
            ], 403);
        }

        return $next($request);
    }
}
