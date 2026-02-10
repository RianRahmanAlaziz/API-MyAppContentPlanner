<?php

namespace App\Policies;

use App\Models\Content;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Auth\Access\Response;

class ContentPolicy
{
    private function workspace(Content $content): Workspace
    {
        return Workspace::findOrFail($content->workspace_id);
    }

    public function view(User $user, Content $content): bool
    {
        if ($user->isAdmin()) return true;

        return $this->workspace($content)->isMember($user->id);
    }

    public function create(User $user, Workspace $workspace): bool
    {
        if ($user->isAdmin()) return true;
        // editor/reviewer/owner boleh create (viewer tidak)
        return $workspace->hasRole($user->id, ['editor', 'reviewer', 'owner']);
    }

    public function update(User $user, Content $content): bool
    {
        if ($user->isAdmin()) return true;

        // editor/owner boleh update
        return $this->workspace($content)->hasRole($user->id, ['editor', 'owner']);
    }

    public function delete(User $user, Content $content): bool
    {
        if ($user->isAdmin()) return true;
        // hanya owner (opsional: editor juga boleh)
        return $this->workspace($content)->hasRole($user->id, ['owner']);
    }

    public function comment(User $user, Content $content): bool
    {
        if ($user->isAdmin()) return true;

        // editor/reviewer/owner boleh comment
        return $this->workspace($content)->hasRole($user->id, ['editor', 'reviewer', 'owner']);
    }

    public function checklist(User $user, Content $content): bool
    {
        if ($user->isAdmin()) return true;

        // editor/owner boleh checklist
        return $this->workspace($content)->hasRole($user->id, ['editor', 'owner']);
    }

    public function approve(User $user, Content $content): bool
    {
        if ($user->isAdmin()) return true;
        // reviewer/owner boleh approve
        return $this->workspace($content)->hasRole($user->id, ['reviewer', 'owner']);
    }
}
