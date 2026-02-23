<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AdminUserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query()->select('id', 'name', 'email', 'role');

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where('name', 'like', "%{$search}%");
        }
        if ($request->boolean('only_unassigned')) {
            $query->whereDoesntHave('workspaceMemberships');
            // atau relasi: workspaces / members sesuai model kamu
        }

        $users = $query->orderBy('name')->paginate(10);

        return response()->json([
            'message' => 'Data semua user berhasil diambil',
            'data' => $users->appends([
                'search' => $request->input('search'),
            ]),
        ], 200);
    }
    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'name' => 'required|max:255',
                'email' => 'required|unique:users,email',
                'role' => 'required',
                'password' => 'nullable|min:2'
            ]);

            if ($request->filled('password')) {
                $validatedData['password'] = Hash::make($validatedData['password']);
            } else {
                unset($validatedData['password']);
            }

            $user = User::create($validatedData);

            // AUDIT: user.created
            audit()->log(
                request: $request,
                event: 'user.created',
                entityType: 'user',
                entityId: (int) $user->id,
                workspaceId: null,
                before: null,
                after: $user->fresh()->only(['id', 'name', 'email', 'role']),
                message: "User created: {$user->email} ({$user->role})"
            );

            return response()->json([
                'success' => true,
                'message' => 'User berhasil dibuat',
                'data' => $user
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat membuat user',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request,  $id)
    {
        try {
            $validatedData = $request->validate([
                'name' => 'required|max:255',
                'email' => 'required|unique:users,email,' . $id,
                'role' => 'required',
                'password' => 'nullable|min:2'
            ]);

            if ($request->filled('password')) {
                $validatedData['password'] = Hash::make($validatedData['password']);
            } else {
                unset($validatedData['password']);
            }

            $user = User::findOrFail($id);
            $before = $user->only(['id', 'name', 'email', 'role']);
            $user->update($validatedData);
            $after = $user->fresh()->only(['id', 'name', 'email', 'role']);

            // AUDIT: user.updated
            audit()->log(
                request: $request,
                event: 'user.updated',
                entityType: 'user',
                entityId: (int) $user->id,
                workspaceId: null,
                before: $before,
                after: $after,
                message: "User updated: {$user->email}",
                meta: [
                    'changes' => [
                        'name'  => ($before['name'] ?? null) !== ($after['name'] ?? null),
                        'email' => ($before['email'] ?? null) !== ($after['email'] ?? null),
                        'role'  => ($before['role'] ?? null) !== ($after['role'] ?? null),
                        'password' => $request->filled('password'),
                    ],
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'User berhasil diperbarui',
                'data' => $user
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memperbarui user',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function destroy(Request $request, $id)
    {
        try {
            $user = User::find($id);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User tidak ditemukan.'
                ], 404);
            }
            $before = $user->only(['id', 'name', 'email', 'role']);
            $user->delete();

            // AUDIT: user.deleted
            audit()->log(
                request: $request,
                event: 'user.deleted',
                entityType: 'user',
                entityId: (int) $before['id'],
                workspaceId: null,
                before: $before,
                after: null,
                message: "User deleted: {$before['email']} ({$before['role']})"
            );
            return response()->json([
                'success' => true,
                'message' => 'User berhasil dihapus.'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus user. Silakan coba lagi.'
            ], 500);
        }
    }
}
