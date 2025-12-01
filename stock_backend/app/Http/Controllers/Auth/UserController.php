<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;

use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('roles')->orderBy('created_at', 'desc')->paginate(20);

        return response()->json($users);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'username'   => 'required|string|max:50|unique:users,username',
            'password'   => 'required|string|min:6',
            'full_name'  => 'nullable|string|max:150',
            'email'      => 'nullable|email|max:150|unique:users,email',
            'is_active'  => 'boolean',
            'role_ids'   => 'array',
            'role_ids.*' => 'exists:roles,id',
        ]);

        $user = new User();
        $user->username      = $data['username'];
        $user->password_hash = Hash::make($data['password']);   // stored in password_hash
        $user->full_name     = $data['full_name'] ?? null;
        $user->email         = $data['email'] ?? null;
        $user->is_active     = $data['is_active'] ?? true;
        $user->save();

        if (!empty($data['role_ids'])) {
            $user->roles()->sync($data['role_ids']);
        }

        return response()->json($user->load('roles'), 201);
    }

    public function show(User $user)
    {
        return response()->json($user->load('roles'));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'username'   => 'sometimes|string|max:50|unique:users,username,' . $user->id,
            'password'   => 'sometimes|string|min:6',
            'full_name'  => 'nullable|string|max:150',
            'email'      => 'nullable|email|max:150|unique:users,email,' . $user->id,
            'is_active'  => 'boolean',
            'role_ids'   => 'array',
            'role_ids.*' => 'exists:roles,id',
        ]);

        if (isset($data['username'])) {
            $user->username = $data['username'];
        }
        if (isset($data['password'])) {
            $user->password_hash = Hash::make($data['password']);
        }
        if (array_key_exists('full_name', $data)) {
            $user->full_name = $data['full_name'];
        }
        if (array_key_exists('email', $data)) {
            $user->email = $data['email'];
        }
        if (array_key_exists('is_active', $data)) {
            $user->is_active = $data['is_active'];
        }

        $user->save();

        if (isset($data['role_ids'])) {
            $user->roles()->sync($data['role_ids']);
        }

        return response()->json($user->load('roles'));
    }

    public function destroy(User $user)
    {
        $user->roles()->detach();
        $user->delete();

        return response()->json(['message' => 'User deleted']);
    }
}
