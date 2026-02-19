<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Http\Request;

class AdminWorkspaceMemberController extends Controller
{
    public function index(Request $request, Workspace $workspace)
    {
        $query = $workspace->members()
            ->with('user:id,name,email,role')
            ->orderByRaw("FIELD(role, 'owner','editor','reviewer','viewer')");

        // Optional search by user name
        if ($request->has('search')) {
            $search = $request->input('search');

            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        // Pagination
        $members = $query->paginate(10);

        // Transform paginated data
        $members->getCollection()->transform(function ($m) use ($workspace) {
            return [
                'user_id'        => $m->user_id,
                'name'           => $m->user->name,
                'email'          => $m->user->email,
                'workspace_role' => $m->role,
                'is_owner'       => (int)$workspace->owner_id === (int)$m->user_id,
            ];
        });

        return response()->json([
            'message' => 'Data member workspace berhasil diambil',
            'meta' => [
                'workspace' => [
                    'id' => $workspace->id,
                    'name' => $workspace->name,
                    'slug' => $workspace->slug ?? null,
                ],
            ],
            'data' => $members->appends([
                'search' => $request->input('search'),
            ]),
        ], 200);
    }

    public function store(Request $request, Workspace $workspace)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'role'  => ['required', 'in:owner,editor,reviewer,viewer'],
        ]);

        $user = User::where('email', $data['email'])->first();
        abort_unless($user, 404, 'User dengan email ini belum terdaftar.');

        $exists = WorkspaceMember::where('workspace_id', $workspace->id)
            ->where('user_id', $user->id)
            ->exists();
        abort_if($exists, 409, 'User sudah menjadi member workspace.');

        // kalau role owner, kita harus transfer ownership
        if ($data['role'] === 'owner') {
            return $this->doTransferOwner($workspace, $user->id, $request);
        }

        $member = WorkspaceMember::create([
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'role' => $data['role'],
        ]);

        return response()->json([
            'message' => 'Member added',
            'data' => [
                'user_id' => $member->user_id,
                'workspace_role' => $member->role,
            ]
        ], 201);
    }

    public function updateRole(Request $request, Workspace $workspace, User $user)
    {
        $data = $request->validate([
            'role' => ['required', 'in:owner,editor,reviewer,viewer'],
        ]);

        // kalau set owner -> gunakan transfer ownership agar konsisten
        if ($data['role'] === 'owner') {
            return $this->doTransferOwner($workspace, $user->id, $request);
        }

        $member = WorkspaceMember::where('workspace_id', $workspace->id)
            ->where('user_id', $user->id)
            ->first();
        abort_unless($member, 404, 'Membership tidak ditemukan.');

        // kalau user ini owner, jangan boleh turunkan lewat updateRole (harus transfer owner)
        abort_if((int)$workspace->owner_id === (int)$user->id, 422, 'Gunakan transfer ownership untuk mengganti owner.');

        $member->role = $data['role'];
        $member->save();

        return response()->json(['message' => 'Role updated']);
    }

    public function destroy(Request $request, Workspace $workspace, User $user)
    {
        // tidak boleh remove owner
        abort_if((int)$workspace->owner_id === (int)$user->id, 422, 'Tidak bisa menghapus owner dari workspace.');

        $deleted = WorkspaceMember::where('workspace_id', $workspace->id)
            ->where('user_id', $user->id)
            ->delete();

        abort_unless($deleted, 404, 'Membership tidak ditemukan.');

        return response()->json(['message' => 'Member removed']);
    }

    public function transferOwner(Request $request, Workspace $workspace)
    {
        $data = $request->validate([
            'new_owner_id' => ['required', 'integer', 'exists:users,id'],
            'old_owner_new_role' => ['nullable', 'in:editor,reviewer,viewer'],
        ]);

        return $this->doTransferOwner(
            $workspace,
            (int)$data['new_owner_id'],
            $request,
            $data['old_owner_new_role'] ?? 'editor'
        );
    }

    private function doTransferOwner(Workspace $workspace, int $newOwnerId, Request $request, string $oldOwnerNewRole = 'editor')
    {
        $oldOwnerId = (int)$workspace->owner_id;
        abort_if($newOwnerId === $oldOwnerId, 422, 'User tersebut sudah menjadi owner.');

        // pastikan member row untuk new owner ada
        $newOwnerMember = WorkspaceMember::firstOrCreate(
            ['workspace_id' => $workspace->id, 'user_id' => $newOwnerId],
            ['role' => 'owner']
        );

        // set new owner role jadi owner
        $newOwnerMember->role = 'owner';
        $newOwnerMember->save();

        // pastikan old owner juga punya membership row (kalau belum ada, create)
        $oldOwnerMember = WorkspaceMember::firstOrCreate(
            ['workspace_id' => $workspace->id, 'user_id' => $oldOwnerId],
            ['role' => 'owner']
        );

        // turunkan role old owner
        $oldOwnerMember->role = $oldOwnerNewRole;
        $oldOwnerMember->save();

        // update owner_id di workspaces
        $workspace->owner_id = $newOwnerId;
        $workspace->save();

        return response()->json([
            'message' => 'Ownership transferred',
            'data' => [
                'workspace_id' => $workspace->id,
                'new_owner_id' => $newOwnerId,
                'old_owner_id' => $oldOwnerId,
                'old_owner_new_role' => $oldOwnerNewRole,
            ],
        ]);
    }
}
