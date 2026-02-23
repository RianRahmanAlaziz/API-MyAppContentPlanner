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
            'assignee_id' => $this->assignee_id,

            'due_at' => $this->due_at,
            'scheduled_at' => $this->scheduled_at,
            'published_at' => $this->published_at,

            'priority' => $this->priority,
            'tags' => $this->tags,

            'created_by' => $this->created_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // relations (akan muncul jika sudah di-load)
            'workspace' => $this->whenLoaded('workspace', function () {
                return [
                    'id' => $this->workspace->id,
                    'name' => $this->workspace->name,
                    'slug' => $this->workspace->slug,
                    'owner_id' => $this->workspace->owner_id,
                ];
            }),

            'assignee' => $this->whenLoaded('assignee', function () {
                return [
                    'id' => $this->assignee->id,
                    'name' => $this->assignee->name,
                    'email' => $this->assignee->email,
                    'role' => $this->assignee->role,
                ];
            }),

            'creator' => $this->whenLoaded('creator', function () {
                return [
                    'id' => $this->creator->id,
                    'name' => $this->creator->name,
                    'email' => $this->creator->email,
                    'role' => $this->creator->role,
                ];
            }),
        ];
    }
}
