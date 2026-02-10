<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Content;
use App\Models\ContentChecklistItem;
use App\Models\Workspace;
use Illuminate\Http\Request;

class ContentChecklistItemController extends Controller
{
    private function ensureMember(Request $request, Content $content): void
    {
        $workspace = Workspace::findOrFail($content->workspace_id);

        $isMember = $workspace->members()
            ->where('user_id', $request->user()->id)
            ->exists();

        abort_unless($isMember, 403);
    }

    private function ensureMemberByItem(Request $request, ContentChecklistItem $item): Content
    {
        $content = Content::findOrFail($item->content_id);
        $this->ensureMember($request, $content);
        return $content;
    }

    public function index(Request $request, Content $content)
    {
        $this->ensureMember($request, $content);

        $items = ContentChecklistItem::query()
            ->where('content_id', $content->id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return response()->json(['data' => $items]);
    }

    public function store(Request $request, Content $content)
    {
        $this->ensureMember($request, $content);

        $data = $request->validate([
            'label' => ['required', 'string', 'max:120'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $nextOrder = $data['sort_order']
            ?? (int) (ContentChecklistItem::where('content_id', $content->id)->max('sort_order') + 1);

        $item = ContentChecklistItem::create([
            'content_id' => $content->id,
            'label' => $data['label'],
            'sort_order' => $nextOrder,
            'is_done' => false,
        ]);

        return response()->json(['data' => $item], 201);
    }

    public function update(Request $request, ContentChecklistItem $item)
    {
        $this->ensureMemberByItem($request, $item);

        $data = $request->validate([
            'label' => ['sometimes', 'string', 'max:120'],
            'is_done' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ]);

        $item->update($data);

        return response()->json(['data' => $item]);
    }

    public function destroy(Request $request, ContentChecklistItem $item)
    {
        $this->ensureMemberByItem($request, $item);

        $item->delete();

        return response()->json(['message' => 'Deleted']);
    }
}
