<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AdminAuditLogController extends Controller
{
    public function index(Request $request)
    {
        $q = AuditLog::query()->with([
            'actor:id,name,email,role',
            'workspace:id,name,slug',
        ]);

        // filters
        if ($request->filled('event')) $q->where('event', $request->string('event'));
        if ($request->filled('entity_type')) $q->where('entity_type', $request->string('entity_type'));
        if ($request->filled('entity_id')) $q->where('entity_id', (int) $request->entity_id);
        if ($request->filled('actor_id')) $q->where('actor_id', (int) $request->actor_id);
        if ($request->filled('workspace_id')) $q->where('workspace_id', (int) $request->workspace_id);

        // search message
        if ($request->filled('q')) {
            $term = $request->string('q');
            $q->where('message', 'like', "%{$term}%");
        }

        // date range
        if ($request->filled('from')) $q->whereDate('created_at', '>=', $request->string('from'));
        if ($request->filled('to')) $q->whereDate('created_at', '<=', $request->string('to'));

        $q->orderByDesc('created_at');

        $perPage = (int) ($request->input('per_page', 20));
        $perPage = max(1, min(100, $perPage));

        $items = $q->paginate($perPage);

        return response()->json([
            'data' => $items->items(),
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
}
