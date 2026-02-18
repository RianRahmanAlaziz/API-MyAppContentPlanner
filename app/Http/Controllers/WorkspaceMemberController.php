<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\WorkspaceMemberResource;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Http\Request;

class WorkspaceMemberController extends Controller
{


    public function index(Request $request, Workspace $workspace)
    {
        $this->authorize('view', $workspace);

        $members = $workspace->members()
            ->with('user:id,name,email,role')
            ->orderBy('role')
            ->get();

        return response()->json([
            'data' => WorkspaceMemberResource::collection($members),
        ]);
    }

    public function store(Request $request, Workspace $workspace)
    {
        $this->authorize('manageMembers', $workspace);


        $data = $request->validate([
            'email' => ['required', 'email'],
            'role'  => ['required', 'in:editor,reviewer,viewer'],
        ]);

        $user = User::where('email', $data['email'])->first();
        abort_unless($user, 404, 'User dengan email ini belum terdaftar.');

        // jangan duplikat
        $exists = WorkspaceMember::where('workspace_id', $workspace->id)
            ->where('user_id', $user->id)
            ->exists();
        abort_if($exists, 409, 'User sudah menjadi member workspace.');

        $member = WorkspaceMember::create([
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'role' => $data['role'],
        ]);

        return response()->json([
            'message' => 'Member added',
            'data' => [
                'user_id' => $member->user_id,
                'role' => $member->role,
            ]
        ], 201);
    }

    public function updateRole(Request $request, Workspace $workspace, User $user)
    {
        $this->authorize('manageMembers', $workspace);

        $data = $request->validate([
            'role' => ['required', 'in:owner,editor,reviewer,viewer'],
        ]);

        // owner tidak diubah via membership role (owner_id di workspaces)
        abort_if($data['role'] === 'owner', 422, 'Gunakan transfer ownership jika ingin ganti owner.');

        $member = WorkspaceMember::where('workspace_id', $workspace->id)
            ->where('user_id', $user->id)
            ->first();
        abort_unless($member, 404);

        // tidak boleh ubah role owner sendiri via table membership (optional rule)
        abort_if($user->id === $workspace->owner_id, 422, 'Tidak bisa mengubah role owner.');

        $member->role = $data['role'];
        $member->save();

        return response()->json(['message' => 'Role updated']);
    }

    public function destroy(Request $request, Workspace $workspace, User $user)
    {
        $this->authorize('manageMembers', $workspace);
        // tidak boleh remove owner
        abort_if($user->id === $workspace->owner_id, 422, 'Tidak bisa menghapus owner dari workspace.');

        $deleted = WorkspaceMember::where('workspace_id', $workspace->id)
            ->where('user_id', $user->id)
            ->delete();

        abort_unless($deleted, 404);

        return response()->json(['message' => 'Member removed']);
    }
}
