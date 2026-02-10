<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Workspace extends Model
{
    protected $fillable = ['name', 'slug', 'owner_id'];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function members()
    {
        return $this->hasMany(WorkspaceMember::class);
    }

    public function isOwner(int $userId): bool
    {
        return $this->owner_id === $userId;
    }

    public function memberRole(int $userId): ?string
    {
        return $this->members()->where('user_id', $userId)->value('role');
    }

    public function roleFor(int $userId): ?string
    {
        return $this->members()->where('user_id', $userId)->value('role');
    }

    public function hasRole(int $userId, array $roles): bool
    {
        $role = $this->roleFor($userId);
        return $role !== null && in_array($role, $roles, true);
    }

    public function isMember(int $userId): bool
    {
        return $this->members()->where('user_id', $userId)->exists();
    }
}
