<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\WorkspaceResource;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WorkspaceController extends Controller
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
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'slug' => 'required|unique:workspaces',
        ]);

        $workspace = Workspace::create([
            'name' => $data['name'],
            'slug' => $data['slug'],
            'owner_id' => $request->user()->id,
        ]);

        // auto add owner as member
        WorkspaceMember::create([
            'workspace_id' => $workspace->id,
            'user_id' => $request->user()->id,
            'role' => 'owner',
        ]);

        return response()->json(['data' => $workspace], 201);
    }

    public function show(Request $request, Workspace $workspace)
    {
        // basic authorization: hanya member boleh lihat
        $isMember = $workspace->members()->where('user_id', $request->user()->id)->exists();
        abort_unless($isMember, 403);

        $workspace->load(['owner:id,name,email', 'members.user:id,name,email']);

        return response()->json(['data' => $workspace]);
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
