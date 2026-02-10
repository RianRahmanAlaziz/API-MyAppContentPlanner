<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'workspace_id' => $this->workspace_id,

            'platform' => $this->platform,
            'content_type' => $this->content_type,

            'title' => $this->title,
            'hook' => $this->hook,

            'script' => $this->script,
            'caption' => $this->caption,
            'hashtags' => $this->hashtags,

            'status' => $this->status,
            'priority' => $this->priority,

            'tags' => $this->tags ?? [],

            'assignee' => new UserResource($this->whenLoaded('assignee')),
            'creator' => new UserResource($this->whenLoaded('creator')),

            'due_at' => $this->due_at,
            'scheduled_at' => $this->scheduled_at,
            'published_at' => $this->published_at,

            'comments' => ContentCommentResource::collection($this->whenLoaded('comments')),
            'checklist_items' => ContentChecklistItemResource::collection($this->whenLoaded('checklistItems')),
            'approvals' => ContentApprovalResource::collection($this->whenLoaded('approvals')),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
