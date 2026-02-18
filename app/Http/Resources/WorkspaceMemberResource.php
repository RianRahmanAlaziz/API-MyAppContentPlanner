<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkspaceMemberResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'workspace_id' => $this->workspace_id,
            'user_id' => $this->user_id,
            'role' => $this->role,
            'user' => new UserResource($this->whenLoaded('user')),
        ];
    }
}
