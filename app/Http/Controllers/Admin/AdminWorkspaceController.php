<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\WorkspaceResource;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminWorkspaceController extends Controller
{
    public function index(Request $request)
    {
        $query = Workspace::with('owner');

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where('name', 'like', "%{$search}%");
        }

        $workspace = $query->paginate(10);

        return response()->json([
            'message' => 'Data semua Workspace berhasil diambil',
            'data' => $workspace->appends([
                'search' => $request->input('search'),
            ]),
        ], 200);
    }

    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'name' => 'required|string|max:120',
                'slug' => 'required|unique:workspaces',
                'owner_id' => 'required',
            ]);

            $workspace  = Workspace::create($validatedData);

            // AUDIT: workspace.created
            audit()->log(
                request: $request,
                event: 'workspace.created',
                entityType: 'workspace',
                entityId: (int) $workspace->id,
                workspaceId: (int) $workspace->id,
                before: null,
                after: $workspace->fresh()->only(['id', 'name', 'slug', 'owner_id']),
                message: "Workspace created: {$workspace->name} ({$workspace->slug})",
                meta: ['owner_id' => $workspace->owner_id]
            );

            return response()->json([
                'success' => true,
                'message' => 'Workspace berhasil dibuat',
                'data' => $workspace
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
                'message' => 'Terjadi kesalahan saat membuat Workspace',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request,  $id)
    {
        try {
            $validatedData = $request->validate([
                'name' => 'required|string|max:120',
                'slug' => 'required|unique:workspaces',
                'owner_id' => 'required',
            ]);

            $workspace = Workspace::findOrFail($id);
            $before = $workspace->only(['id', 'name', 'slug', 'owner_id']);

            $workspace->update($validatedData);

            $after = $workspace->fresh()->only(['id', 'name', 'slug', 'owner_id']);
            // AUDIT: workspace.updated
            audit()->log(
                request: $request,
                event: 'workspace.updated',
                entityType: 'workspace',
                entityId: (int) $workspace->id,
                workspaceId: (int) $workspace->id,
                before: $before,
                after: $after,
                message: "Workspace updated: {$after['name']} ({$after['slug']})",
                meta: [
                    'changes' => [
                        'name' => ($before['name'] ?? null) !== ($after['name'] ?? null),
                        'slug' => ($before['slug'] ?? null) !== ($after['slug'] ?? null),
                        'owner_id' => ($before['owner_id'] ?? null) !== ($after['owner_id'] ?? null),
                    ],
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Workspace berhasil diperbarui',
                'data' => $workspace
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
                'message' => 'Terjadi kesalahan saat memperbarui Workspace',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Request $request, $id)
    {
        $workspace = Workspace::find($id);

        try {
            if (!$workspace) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data Workspace tidak ditemukan.'
                ], 404);
            }

            $before = $workspace->only(['id', 'name', 'slug', 'owner_id']);

            $workspace->delete();

            // AUDIT: workspace.deleted
            audit()->log(
                request: $request,
                event: 'workspace.deleted',
                entityType: 'workspace',
                entityId: (int) $before['id'],
                workspaceId: (int) $before['id'],
                before: $before,
                after: null,
                message: "Workspace deleted: {$before['name']} ({$before['slug']})",
                meta: ['owner_id' => $before['owner_id'] ?? null]
            );

            return response()->json([
                'success' => true,
                'message' => 'Data Workspace berhasil dihapus.'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus Data Workspace. Silakan coba lagi.'
            ], 500);
        }
    }
}
