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

            $data = Workspace::create($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Workspace berhasil dibuat',
                'data' => $data
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
            $workspace->update($validatedData);

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

    public function destroy($id)
    {
        $workspace = Workspace::find($id);

        try {
            if (!$workspace) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data Workspace tidak ditemukan.'
                ], 404);
            }

            $workspace->delete();

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
