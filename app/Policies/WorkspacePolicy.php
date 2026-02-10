<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Auth\Access\Response;

class WorkspacePolicy
{
    public function view(User $user, Workspace $workspace): bool
    {
        if ($user->isAdmin()) return true;
        return $workspace->isMember($user->id);
    }

    public function create(User $user): bool
    {
        return true; // semua user boleh bikin workspace
    }

    public function manageMembers(User $user, Workspace $workspace): bool
    {
        if ($user->isAdmin()) return true;
        return $workspace->owner_id === $user->id;
    }
}
