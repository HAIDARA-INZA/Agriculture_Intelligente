<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApiAuth
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $plain = $request->bearerToken();
        if (!$plain) {
            return response()->json(['message' => 'Non authentifié'], 401);
        }
        $row = DB::table('api_tokens')->where('token_hash', hash('sha256', $plain))->where('expires_at', '>', now())->first();
        if (!$row) {
            return response()->json(['message' => 'Session expirée, reconnectez-vous'], 401);
        }
        $user = DB::table('users')->where('id', $row->user_id)->first();
        if (!$user) {
            return response()->json(['message' => 'Non authentifié'], 401);
        }
        if ($roles && !in_array($user->role, $roles)) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }
        $request->attributes->set('auth_user', $user);
        return $next($request);
    }
}
