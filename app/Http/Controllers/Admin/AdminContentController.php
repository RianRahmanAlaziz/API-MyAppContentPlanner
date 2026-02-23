<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ContentResource;
use App\Models\Content;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminContentController extends Controller
{
    public function index(Request $request)
    {
        $q = Content::query()
            ->with([
                'workspace:id,name,slug,owner_id',
                'assignee:id,name,email,role',
                'creator:id,name,email,role',
            ]);

        // filters
        if ($request->filled('workspace_id')) {
            $q->where('workspace_id', (int) $request->workspace_id);
        }

        if ($request->filled('status')) {
            $q->where('status', $request->string('status'));
        }

        if ($request->filled('platform')) {
            $q->where('platform', $request->string('platform'));
        }

        if ($request->filled('assignee_id')) {
            $q->where('assignee_id', (int) $request->assignee_id);
        }

        if ($request->filled('priority')) {
            $q->where('priority', $request->string('priority'));
        }

        // search
        if ($request->filled('q')) {
            $term = (string) $request->q;
            $q->where(function ($w) use ($term) {
                $w->where('title', 'like', "%{$term}%")
                    ->orWhere('hook', 'like', "%{$term}%")
                    ->orWhere('caption', 'like', "%{$term}%");
            });
        }

        // date range
        $dateField = $request->string('date_field')->toString() ?: 'scheduled_at';
        $allowedDateFields = ['scheduled_at', 'due_at', 'published_at', 'created_at'];

        if (!in_array($dateField, $allowedDateFields, true)) {
            $dateField = 'scheduled_at';
        }

        if ($request->filled('from')) {
            $q->where($dateField, '>=', $request->string('from'));
        }
        if ($request->filled('to')) {
            $q->where($dateField, '<=', $request->string('to'));
        }

        // sorting
        $sort = $request->string('sort')->toString() ?: '-created_at';
        $allowedSorts = ['created_at', 'scheduled_at', 'due_at', 'published_at', 'priority', 'status'];

        $direction = 'asc';
        $field = $sort;

        if (str_starts_with($sort, '-')) {
            $direction = 'desc';
            $field = ltrim($sort, '-');
        }

        if (!in_array($field, $allowedSorts, true)) {
            $field = 'created_at';
            $direction = 'desc';
        }

        $q->orderBy($field, $direction);

        $perPage = (int) ($request->input('per_page', 20));
        $perPage = max(1, min(100, $perPage));

        $items = $q->paginate($perPage);

        return response()->json([
            'data' => ContentResource::collection($items),
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
            ],
            'links' => [
                'first' => $items->url(1),
                'last' => $items->url($items->lastPage()),
                'prev' => $items->previousPageUrl(),
                'next' => $items->nextPageUrl(),
            ],
        ]);
    }

    public function show(Content $content)
    {
        $content->load([
            'workspace:id,name,slug,owner_id',
            'assignee:id,name,email,role',
            'creator:id,name,email,role',
            // kalau ada relasi lain (comments, checklistItems, approvals) bisa ditambahkan
        ]);

        return response()->json([
            'data' => new ContentResource($content),
        ]);
    }

    /**
     * PUT /api/admin/contents/{content}
     */
    public function update(Request $request, Content $content)
    {
        $data = $request->validate([
            // admin boleh pindahin content ke workspace lain? optional:
            'workspace_id' => ['sometimes', 'integer', 'exists:workspaces,id'],

            'platform' => ['sometimes', 'in:ig,tiktok,youtube'],
            'content_type' => ['sometimes', 'string', 'max:50'],
            'title' => ['sometimes', 'string', 'max:200'],
            'hook' => ['nullable', 'string', 'max:255'],
            'script' => ['nullable', 'string'],
            'caption' => ['nullable', 'string'],
            'hashtags' => ['nullable', 'string'],

            // kalau kamu ingin status via move saja, hapus baris status ini:
            'status' => ['sometimes', 'in:idea,brief,production,review,scheduled,published'],

            'assignee_id' => ['nullable', 'integer', 'exists:users,id'],
            'due_at' => ['nullable', 'date'],
            'scheduled_at' => ['nullable', 'date'],
            'published_at' => ['nullable', 'date'],
            'priority' => ['nullable', 'in:low,med,high'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:30'],
        ]);

        // default priority kalau kosong & belum pernah ada
        if (!array_key_exists('priority', $data) && !$content->priority) {
            $data['priority'] = 'med';
        }

        // kalau status ikut diupdate di sini, normalisasi tanggal
        $effective = array_merge($content->toArray(), $data);
        $this->normalizeDatesByStatus($data, $effective['status'] ?? $content->status);

        $before = $content->toArray();
        $content->update($data);
        $after = $content->fresh()->toArray();

        audit()->logModel(
            request: $request,
            event: 'content.updated',
            model: $content,
            workspaceId: $content->workspace_id,
            before: $before,
            after: $after,
            message: "Content updated: {$content->title}"
        );

        $content->load([
            'workspace:id,name,slug,owner_id',
            'assignee:id,name,email,role',
            'creator:id,name,email,role',
        ]);

        return response()->json([
            'data' => new ContentResource($content),
        ]);
    }

    /**
     * PATCH /api/admin/contents/{content}/move
     * body: { status: "review" }
     */
    public function move(Request $request, Content $content)
    {
        $data = $request->validate([
            'status' => ['required', 'in:idea,brief,production,review,scheduled,published'],
            // optional: kalau move ke scheduled, kamu bisa izinkan kirim scheduled_at langsung
            'scheduled_at' => ['nullable', 'date'],
            // optional: kalau move ke published, kamu bisa izinkan published_at
            'published_at' => ['nullable', 'date'],
        ]);

        $content->status = $data['status'];

        // aturan konsistensi tanggal
        if ($data['status'] !== 'scheduled') {
            $content->scheduled_at = null;
        } else {
            // kalau scheduled tapi tidak kirim scheduled_at, biarkan existing atau validasi tambahan (optional)
            if (!empty($data['scheduled_at'])) $content->scheduled_at = $data['scheduled_at'];
        }

        if ($data['status'] !== 'published') {
            $content->published_at = null;
        } else {
            if (!empty($data['published_at'])) $content->published_at = $data['published_at'];
        }

        $before = $content->toArray();
        // ... ubah status ...
        $content->save();
        $after = $content->fresh()->toArray();

        audit()->logModel(
            request: $request,
            event: 'content.status_moved',
            model: $content,
            workspaceId: $content->workspace_id,
            before: $before,
            after: $after,
            message: "Content moved to {$content->status}: {$content->title}",
            meta: ['from_status' => $before['status'] ?? null, 'to_status' => $after['status'] ?? null]
        );

        $content->load([
            'workspace:id,name,slug,owner_id',
            'assignee:id,name,email,role',
            'creator:id,name,email,role',
        ]);

        return response()->json([
            'data' => new ContentResource($content),
        ]);
    }

    /**
     * DELETE /api/admin/contents/{content}
     */
    public function destroy(Request $request, Content $content)
    {

        $before = $content->toArray();
        $content->delete();

        audit()->log(
            request: $request,
            event: 'content.deleted',
            entityType: 'content',
            entityId: (int) $before['id'],
            workspaceId: (int) ($before['workspace_id'] ?? null),
            before: $before,
            after: null,
            message: "Content deleted: " . ($before['title'] ?? ('#' . $before['id']))
        );

        return response()->json([
            'message' => 'Deleted',
        ]);
    }

    /**
     * Helper: normalize scheduled_at/published_at based on status.
     * - status != scheduled => scheduled_at null
     * - status != published => published_at null
     */
    private function normalizeDatesByStatus(array &$data, ?string $statusOverride = null): void
    {
        $status = $statusOverride ?? ($data['status'] ?? null);
        if (!$status) return;

        if ($status !== 'scheduled') {
            $data['scheduled_at'] = null;
        }

        if ($status !== 'published') {
            $data['published_at'] = null;
        }
    }
}
