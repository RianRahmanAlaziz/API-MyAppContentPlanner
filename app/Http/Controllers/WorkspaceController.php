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
        // list workspace yang user jadi membernya
        $workspaces = Workspace::query()
            ->whereHas('members', fn($q) => $q->where('user_id', $request->user()->id))
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => WorkspaceResource::collection($workspaces),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
        ]);

        $slug = Str::slug($data['name']) . '-' . Str::lower(Str::random(6));

        $workspace = Workspace::create([
            'name' => $data['name'],
            'slug' => $slug,
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
}
