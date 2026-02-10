<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Content;
use App\Models\ContentApproval;
use App\Models\Workspace;
use Illuminate\Http\Request;

class ContentApprovalController extends Controller
{
    private function getWorkspace(Content $content): Workspace
    {
        return Workspace::findOrFail($content->workspace_id);
    }

    private function ensureMember(Request $request, Workspace $workspace): void
    {
        $isMember = $workspace->members()
            ->where('user_id', $request->user()->id)
            ->exists();

        abort_unless($isMember, 403);
    }

    private function ensureReviewerOrOwner(Request $request, Workspace $workspace): void
    {
        $member = $workspace->members()
            ->where('user_id', $request->user()->id)
            ->first();

        abort_unless($member, 403);

        $role = $member->role;

        // owner boleh approve juga
        abort_unless(in_array($role, ['reviewer', 'owner'], true), 403);
    }

    public function index(Request $request, Content $content)
    {
        $workspace = $this->getWorkspace($content);
        $this->ensureMember($request, $workspace);

        $items = ContentApproval::query()
            ->where('content_id', $content->id)
            ->with('reviewer:id,name,email')
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['data' => $items]);
    }

    public function approve(Request $request, Content $content)
    {
        $workspace = $this->getWorkspace($content);
        $this->ensureReviewerOrOwner($request, $workspace);

        $data = $request->validate([
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        $approval = ContentApproval::create([
            'content_id' => $content->id,
            'reviewer_id' => $request->user()->id,
            'status' => 'approved',
            'note' => $data['note'] ?? null,
        ]);

        // aturan workflow:
        // kalau approve saat status review -> pindah ke scheduled (tanpa set tanggal)
        if ($content->status === 'review') {
            $content->status = 'scheduled';
            $content->save();
        }

        $approval->load('reviewer:id,name,email');

        return response()->json(['data' => $approval], 201);
    }

    public function requestChanges(Request $request, Content $content)
    {
        $workspace = $this->getWorkspace($content);
        $this->ensureReviewerOrOwner($request, $workspace);

        $data = $request->validate([
            'note' => ['required', 'string', 'max:2000'],
        ]);

        $approval = ContentApproval::create([
            'content_id' => $content->id,
            'reviewer_id' => $request->user()->id,
            'status' => 'changes_requested',
            'note' => $data['note'],
        ]);

        // aturan workflow:
        // kalau minta revisi, balikin ke production (atau brief) — pilih salah satu
        if (in_array($content->status, ['review', 'scheduled'], true)) {
            $content->status = 'production';
            $content->scheduled_at = null;
            $content->save();
        }

        $approval->load('reviewer:id,name,email');

        return response()->json(['data' => $approval], 201);
    }
}
