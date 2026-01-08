<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    private function ensureAdmin(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            abort(403, 'Admin only');
        }
    }

    /**
     * LIST USERS (default: sales)
     */
    public function index(Request $request)
    {
        $this->ensureAdmin($request);

        $q = User::query()
            ->select(['id','name','email','role','is_active'])
            ->orderBy('name');

        if ($request->filled('role')) {
            $q->where('role', $request->role);
        } else {
            $q->where('role', 'sales');
        }

        if ($request->filled('q')) {
            $kw = trim($request->q);
            $q->where(function ($qq) use ($kw) {
                $qq->where('name', 'like', "%{$kw}%")
                   ->orWhere('email', 'like', "%{$kw}%");
            });
        }

        return response()->json([
            'data' => $q->get(),
        ]);
    }

    /**
     * CREATE USER (sales / admin)
     */
    public function store(Request $request)
    {
        $this->ensureAdmin($request);

        $data = $request->validate([
            'name'     => ['required','string','max:255'],
            'email'    => ['required','email','max:255','unique:users,email'],
            'role'     => ['required', Rule::in(['sales','admin'])],
            'password' => ['nullable','string','min:6'],
        ]);

        $user = User::create([
            'name'      => $data['name'],
            'email'     => $data['email'],
            'role'      => $data['role'],
            'is_active' => 1,
            'password'  => Hash::make(
                $data['password'] ?? 'password123'
            ),
        ]);

        return response()->json([
            'message' => 'User created',
            'user' => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'role'  => $user->role,
            ]
        ], 201);
    }

    /**
     * TOGGLE ACTIVE (enable / disable login)
     */
    public function toggleActive(Request $request, User $user)
    {
        $this->ensureAdmin($request);

        if ($user->id === $request->user()->id) {
            abort(400, 'Tidak boleh menonaktifkan diri sendiri');
        }

        $user->is_active = !$user->is_active;
        $user->save();

        return response()->json([
            'message' => 'Status updated',
            'is_active' => $user->is_active,
        ]);
    }

    /**
     * RESET PASSWORD
     */
    public function resetPassword(Request $request, User $user)
    {
        $this->ensureAdmin($request);

        $data = $request->validate([
            'password' => ['required','string','min:6'],
        ]);

        $user->password = Hash::make($data['password']);
        $user->save();

        return response()->json([
            'message' => 'Password reset',
        ]);
    }
}
