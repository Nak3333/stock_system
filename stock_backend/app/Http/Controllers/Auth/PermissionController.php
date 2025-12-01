<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    public function index()
    {
        return response()->json(Permission::all());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code'        => 'required|string|max:100|unique:permissions,code',
            'name'        => 'required|string|max:100',
            'description' => 'nullable|string',
        ]);

        $permission = Permission::create($data);

        return response()->json($permission, 201);
    }

    public function show(Permission $permission)
    {
        return response()->json($permission);
    }

    public function update(Request $request, Permission $permission)
    {
        $data = $request->validate([
            'code'        => 'sometimes|string|max:100|unique:permissions,code,' . $permission->id,
            'name'        => 'sometimes|string|max:100',
            'description' => 'nullable|string',
        ]);

        $permission->update($data);

        return response()->json($permission);
    }

    public function destroy(Permission $permission)
    {
        $permission->delete();

        return response()->json(['message' => 'Permission deleted']);
    }
}
