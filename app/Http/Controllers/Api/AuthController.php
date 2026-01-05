<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class AuthController extends Controller
{
    /**
     * LOGIN
     */
    public function login(Request $request)
    {
        // ===== DEBUG REQUEST (AMAN DI LOCAL) =====
        Log::info('LOGIN REQUEST', [
            'content_type' => $request->header('content-type'),
            'accept'       => $request->header('accept'),
            'payload'      => $request->all(),
        ]);

        // ===== VALIDASI =====
        $data = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        // ===== CARI USER =====
        $user = User::where('email', $data['email'])->first();

        // ===== DEBUG USER =====
        Log::info('LOGIN USER CHECK', [
            'user_found' => (bool) $user,
            'is_active'  => $user?->is_active,
            'hash_ok'    => $user ? Hash::check($data['password'], $user->password) : null,
        ]);

        // ===== CEK USER & PASSWORD =====
        if (!$user || !Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau password salah.'],
            ]);
        }

        // ===== CEK AKUN AKTIF =====
        if ((int) $user->is_active !== 1) {
            throw ValidationException::withMessages([
                'email' => ['Akun nonaktif.'],
            ]);
        }

        // ===== HAPUS TOKEN LAMA =====
        $user->tokens()->delete();

        // ===== BUAT TOKEN BARU =====
        $token = $user->createToken('mobile')->plainTextToken;

        // ===== RESPONSE =====
        return response()->json([
            'token' => $token,
            'user'  => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'role'  => $user->role,
            ],
        ]);
    }

    /**
     * USER INFO (AUTH)
     */
    public function me(Request $request)
    {
        return response()->json([
            'id'    => $request->user()->id,
            'name'  => $request->user()->name,
            'email' => $request->user()->email,
            'role'  => $request->user()->role,
        ]);
    }

    /**
     * LOGOUT
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out',
        ]);
    }
}
