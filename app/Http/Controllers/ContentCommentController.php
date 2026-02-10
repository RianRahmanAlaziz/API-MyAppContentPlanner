<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Content;
use App\Models\ContentComment;
use App\Models\Workspace;
use Illuminate\Http\Request;

class ContentCommentController extends Controller
{

    public function index(Request $request, Content $content)
    {
        $this->authorize('view', $content);

        $comments = ContentComment::query()
            ->where('content_id', $content->id)
            ->with('user:id,name,email')
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json(['data' => $comments]);
    }

    public function store(Request $request, Content $content)
    {
        $this->authorize('comment', $content);

        $data = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $comment = ContentComment::create([
            'content_id' => $content->id,
            'user_id' => $request->user()->id,
            'message' => $data['message'],
        ]);

        $comment->load('user:id,name,email');

        return response()->json(['data' => $comment], 201);
    }
}
