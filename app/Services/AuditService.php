<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;

class AuditService
{
    public function log(
        Request $request,
        string $event,
        string $entityType,
        ?int $entityId = null,
        ?int $workspaceId = null,
        ?array $before = null,
        ?array $after = null,
        ?string $message = null,
        array $meta = []
    ): AuditLog {
        $actorId = optional($request->user())->id;

        $baseMeta = [
            'ip' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 500),
            'path' => $request->path(),
            'method' => $request->method(),
        ];

        return AuditLog::create([
            'actor_id' => $actorId,
            'workspace_id' => $workspaceId,
            'event' => $event,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'message' => $message,
            'before' => $before,
            'after' => $after,
            'meta' => array_merge($baseMeta, $meta),
        ]);
    }

    /**
     * Helper: log based on Eloquent model.
     */
    public function logModel(
        Request $request,
        string $event,
        Model $model,
        ?int $workspaceId = null,
        ?array $before = null,
        ?array $after = null,
        ?string $message = null,
        array $meta = []
    ): AuditLog {
        return $this->log(
            request: $request,
            event: $event,
            entityType: $this->guessEntityType($model),
            entityId: (int) $model->getKey(),
            workspaceId: $workspaceId,
            before: $before,
            after: $after,
            message: $message,
            meta: $meta
        );
    }

    private function guessEntityType(Model $model): string
    {
        // Content -> content, WorkspaceMember -> membership, etc.
        $short = class_basename($model);
        return match ($short) {
            'Content' => 'content',
            'Workspace' => 'workspace',
            'User' => 'user',
            'WorkspaceMember' => 'membership',
            default => strtolower($short),
        };
    }
}
