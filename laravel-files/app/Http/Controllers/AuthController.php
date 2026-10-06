<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    private function issueToken(int $userId): string
    {
        $plain = Str::random(60);
        DB::table('api_tokens')->insert([
            'user_id' => $userId, 'token_hash' => hash('sha256', $plain),
            'created_at' => now(), 'expires_at' => now()->addDays(7),
        ]);
        return $plain;
    }

    private function publicUser($u): array
    {
        return ['id' => $u->id, 'name' => $u->name, 'email' => $u->email, 'role' => $u->role];
    }

    public function register(Request $request)
    {
        $d = $request->validate([
            'name' => 'required|string|min:2|max:120',
            'email' => 'required|email|max:150',
            'password' => 'required|string|min:8|max:100',
        ]);
        $email = strtolower(trim($d['email']));
        if (DB::table('users')->where('email', $email)->exists()) {
            return response()->json(['message' => 'Cet e-mail est déjà inscrit'], 422);
        }
        $id = DB::table('users')->insertGetId([
            'name' => $d['name'], 'email' => $email, 'password' => Hash::make($d['password']),
            'role' => 'producteur', 'created_at' => now(), 'updated_at' => now(),
        ]);
        return response()->json(['token' => $this->issueToken($id), 'user' => $this->publicUser(DB::table('users')->find($id))], 201);
    }

    public function login(Request $request)
    {
        $d = $request->validate(['email' => 'required|email', 'password' => 'required|string']);
        $user = DB::table('users')->where('email', strtolower(trim($d['email'])))->first();
        if (!$user || !Hash::check($d['password'], $user->password)) {
            return response()->json(['message' => 'E-mail ou mot de passe incorrect'], 401);
        }
        return response()->json(['token' => $this->issueToken($user->id), 'user' => $this->publicUser($user)]);
    }

    public function me(Request $request)
    {
        return response()->json(['user' => $this->publicUser($request->attributes->get('auth_user'))]);
    }

    public function logout(Request $request)
    {
        DB::table('api_tokens')->where('token_hash', hash('sha256', $request->bearerToken()))->delete();
        return response()->json(['message' => 'Déconnecté']);
    }
}
