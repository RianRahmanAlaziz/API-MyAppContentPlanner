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

        // AUDIT: membership.added
        audit()->log(
            request: $request,
            event: 'membership.added',
            entityType: 'membership',
            entityId: (int) $member->id,
            workspaceId: (int) $workspace->id,
            before: null,
            after: $member->fresh()->toArray(),
            message: "Member added: {$user->email} as {$member->role}",
            meta: [
                'user_id' => (int) $user->id,
                'email' => $user->email,
                'role' => $member->role,
            ]
        );

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

        $before = $member->toArray();
        $member->role = $data['role'];
        $member->save();
        $after = $member->fresh()->toArray();

        // AUDIT: membership.role_changed
        audit()->log(
            request: $request,
            event: 'membership.role_changed',
            entityType: 'membership',
            entityId: (int) $member->id,
            workspaceId: (int) $workspace->id,
            before: $before,
            after: $after,
            message: "Role changed: {$user->email} {$before['role']} → {$after['role']}",
            meta: [
                'user_id' => (int) $user->id,
                'email' => $user->email,
                'from_role' => $before['role'] ?? null,
                'to_role' => $after['role'] ?? null,
            ]
        );

        return response()->json(['message' => 'Role updated']);
    }

    public function destroy(Request $request, Workspace $workspace, User $user)
    {
        // tidak boleh remove owner
        abort_if((int)$workspace->owner_id === (int)$user->id, 422, 'Tidak bisa menghapus owner dari workspace.');

        $member = WorkspaceMember::where('workspace_id', $workspace->id)
            ->where('user_id', $user->id)
            ->first();

        abort_unless($member, 404, 'Membership tidak ditemukan.');

        $before = $member->toArray();

        $member->delete();
        // AUDIT: membership.removed
        audit()->log(
            request: $request,
            event: 'membership.removed',
            entityType: 'membership',
            entityId: (int) ($before['id'] ?? 0),
            workspaceId: (int) $workspace->id,
            before: $before,
            after: null,
            message: "Member removed: {$user->email} (role: {$before['role']})",
            meta: [
                'user_id' => (int) $user->id,
                'email' => $user->email,
                'role' => $before['role'] ?? null,
            ]
        );

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
        $oldOwnerId = (int) $workspace->owner_id;
        abort_if($newOwnerId === $oldOwnerId, 422, 'User tersebut sudah menjadi owner.');

        // BEFORE snapshots (untuk audit workspace.updated + membership.role_changed)
        $workspaceBefore = $workspace->only(['id', 'name', 'slug', 'owner_id']);

        $oldOwnerUser = User::select('id', 'email', 'name')->find($oldOwnerId);
        $newOwnerUser = User::select('id', 'email', 'name')->find($newOwnerId);

        // pastikan member row untuk new owner ada
        $newOwnerMember = WorkspaceMember::firstOrCreate(
            ['workspace_id' => $workspace->id, 'user_id' => $newOwnerId],
            ['role' => 'owner']
        );
        $newOwnerMemberBefore = $newOwnerMember->toArray();

        // set new owner role jadi owner
        $newOwnerMember->role = 'owner';
        $newOwnerMember->save();
        $newOwnerMemberAfter = $newOwnerMember->fresh()->toArray();

        // pastikan old owner juga punya membership row (kalau belum ada, create)
        $oldOwnerMember = WorkspaceMember::firstOrCreate(
            ['workspace_id' => $workspace->id, 'user_id' => $oldOwnerId],
            ['role' => 'owner']
        );
        $oldOwnerMemberBefore = $oldOwnerMember->toArray();

        // turunkan role old owner
        $oldOwnerMember->role = $oldOwnerNewRole;
        $oldOwnerMember->save();
        $oldOwnerMemberAfter = $oldOwnerMember->fresh()->toArray();

        // update owner_id di workspaces
        $workspace->owner_id = $newOwnerId;
        $workspace->save();

        $workspaceAfter = $workspace->fresh()->only(['id', 'name', 'slug', 'owner_id']);

        /**
         * AUDIT 1: membership.role_changed (new owner)
         * - kalau awalnya sudah role owner, ini tetap akan log (opsional).
         *   Kalau mau strict, bisa abort_if role sama untuk skip.
         */
        audit()->log(
            request: $request,
            event: 'membership.role_changed',
            entityType: 'membership',
            entityId: (int) $newOwnerMember->id,
            workspaceId: (int) $workspace->id,
            before: $newOwnerMemberBefore,
            after: $newOwnerMemberAfter,
            message: "Role changed (transfer): " .
                (($newOwnerUser->email ?? "user#{$newOwnerId}") . " " .
                    (($newOwnerMemberBefore['role'] ?? null) . " → " . ($newOwnerMemberAfter['role'] ?? null))),
            meta: [
                'transfer_owner' => true,
                'user_id' => $newOwnerId,
                'email' => $newOwnerUser->email ?? null,
                'from_role' => $newOwnerMemberBefore['role'] ?? null,
                'to_role' => $newOwnerMemberAfter['role'] ?? null,
            ]
        );

        /**
         * AUDIT 2: membership.role_changed (old owner)
         */
        audit()->log(
            request: $request,
            event: 'membership.role_changed',
            entityType: 'membership',
            entityId: (int) $oldOwnerMember->id,
            workspaceId: (int) $workspace->id,
            before: $oldOwnerMemberBefore,
            after: $oldOwnerMemberAfter,
            message: "Role changed (transfer): " .
                (($oldOwnerUser->email ?? "user#{$oldOwnerId}") . " " .
                    (($oldOwnerMemberBefore['role'] ?? null) . " → " . ($oldOwnerMemberAfter['role'] ?? null))),
            meta: [
                'transfer_owner' => true,
                'user_id' => $oldOwnerId,
                'email' => $oldOwnerUser->email ?? null,
                'from_role' => $oldOwnerMemberBefore['role'] ?? null,
                'to_role' => $oldOwnerMemberAfter['role'] ?? null,
            ]
        );

        /**
         * AUDIT 3: workspace.updated (karena owner_id berubah)
         */
        audit()->log(
            request: $request,
            event: 'workspace.updated',
            entityType: 'workspace',
            entityId: (int) $workspace->id,
            workspaceId: (int) $workspace->id,
            before: $workspaceBefore,
            after: $workspaceAfter,
            message: "Workspace owner changed: {$workspaceBefore['owner_id']} → {$workspaceAfter['owner_id']}",
            meta: [
                'transfer_owner' => true,
                'from_owner_id' => $workspaceBefore['owner_id'] ?? null,
                'to_owner_id' => $workspaceAfter['owner_id'] ?? null,
                'from_owner_email' => $oldOwnerUser->email ?? null,
                'to_owner_email' => $newOwnerUser->email ?? null,
                'old_owner_new_role' => $oldOwnerNewRole,
            ]
        );

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
