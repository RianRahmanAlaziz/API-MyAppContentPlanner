<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Content;
use App\Models\Workspace;
use Illuminate\Http\Request;

class ContentController extends Controller
{

    public function index(Request $request, Workspace $workspace)
    {
        $this->authorize('view', $workspace);

        $q = Content::query()->where('workspace_id', $workspace->id);

        // filters
        if ($request->filled('status')) $q->where('status', $request->string('status'));
        if ($request->filled('platform')) $q->where('platform', $request->string('platform'));
        if ($request->filled('assignee_id')) $q->where('assignee_id', (int)$request->assignee_id);

        // date range for scheduled (calendar)
        if ($request->filled('from')) $q->where('scheduled_at', '>=', $request->string('from'));
        if ($request->filled('to')) $q->where('scheduled_at', '<=', $request->string('to'));

        // search
        if ($request->filled('q')) {
            $term = $request->string('q');
            $q->where(function ($w) use ($term) {
                $w->where('title', 'like', "%{$term}%")
                    ->orWhere('hook', 'like', "%{$term}%")
                    ->orWhere('caption', 'like', "%{$term}%");
            });
        }

        $items = $q->with(['assignee:id,name,email', 'creator:id,name,email'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json($items);
    }

    public function store(Request $request, Workspace $workspace)
    {
        $this->authorize('create', [Content::class, $workspace]);


        $data = $request->validate([
            'platform' => ['required', 'in:ig,tiktok,youtube'],
            'content_type' => ['required', 'string', 'max:50'],
            'title' => ['required', 'string', 'max:200'],
            'hook' => ['nullable', 'string', 'max:255'],
            'script' => ['nullable', 'string'],
            'caption' => ['nullable', 'string'],
            'hashtags' => ['nullable', 'string'],
            'status' => ['required', 'in:idea,brief,production,review,scheduled,published'],
            'assignee_id' => ['nullable', 'integer', 'exists:users,id'],
            'due_at' => ['nullable', 'date'],
            'scheduled_at' => ['nullable', 'date'],
            'published_at' => ['nullable', 'date'],
            'priority' => ['nullable', 'in:low,med,high'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:30'],
        ]);

        $data['workspace_id'] = $workspace->id;
        $data['created_by'] = $request->user()->id;
        $data['priority'] = $data['priority'] ?? 'med';

        $content = Content::create($data);

        return response()->json(['data' => $content], 201);
    }

    public function show(Request $request, Content $content)
    {
        $this->authorize('view', $content);


        $content->load([
            'assignee:id,name,email',
            'creator:id,name,email',
            'comments.user:id,name,email',
            'checklistItems',
            'approvals.reviewer:id,name,email',
        ]);

        $workspace = Workspace::findOrFail($content->workspace_id);

        // Optional: info workspace + role user saat ini (berguna untuk FE)
        $myRole = $workspace->members()
            ->where('user_id', $request->user()->id)
            ->value('role');

        return response()->json([
            'data' => $content,
            'meta' => [
                'workspace' => [
                    'id' => $workspace->id,
                    'name' => $workspace->name,
                    'slug' => $workspace->slug,
                ],
                'my_role' => $myRole,
            ],
        ]);
    }


    public function update(Request $request, Content $content)
    {
        $this->authorize('update', $content);

        $data = $request->validate([
            'platform' => ['sometimes', 'in:ig,tiktok,youtube'],
            'content_type' => ['sometimes', 'string', 'max:50'],
            'title' => ['sometimes', 'string', 'max:200'],
            'hook' => ['nullable', 'string', 'max:255'],
            'script' => ['nullable', 'string'],
            'caption' => ['nullable', 'string'],
            'hashtags' => ['nullable', 'string'],
            'status' => ['sometimes', 'in:idea,brief,production,review,scheduled,published'],
            'assignee_id' => ['nullable', 'integer', 'exists:users,id'],
            'due_at' => ['nullable', 'date'],
            'scheduled_at' => ['nullable', 'date'],
            'published_at' => ['nullable', 'date'],
            'priority' => ['nullable', 'in:low,med,high'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:30'],
        ]);

        $content->update($data);

        return response()->json(['data' => $content]);
    }

    public function destroy(Request $request, Content $content)
    {
        $this->authorize('delete', $content);

        $content->delete();

        return response()->json(['message' => 'Deleted']);
    }

    public function move(Request $request, Content $content)
    {
        $this->authorize('update', $content);
        $data = $request->validate([
            'status' => ['required', 'in:idea,brief,production,review,scheduled,published'],
        ]);

        $content->status = $data['status'];

        // aturan kecil biar konsisten
        if ($data['status'] !== 'scheduled') $content->scheduled_at = null;
        if ($data['status'] !== 'published') $content->published_at = null;

        $content->save();

        return response()->json(['data' => $content]);
    }

    public function schedule(Request $request, Content $content)
    {
        $this->authorize('update', $content);

        $data = $request->validate([
            'scheduled_at' => ['required', 'date'],
        ]);

        $content->scheduled_at = $data['scheduled_at'];
        $content->status = 'scheduled';
        $content->save();

        return response()->json(['data' => $content]);
    }
}
