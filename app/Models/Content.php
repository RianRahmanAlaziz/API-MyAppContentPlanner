<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Content extends Model
{
    protected $fillable = [
        'workspace_id',
        'platform',
        'content_type',
        'title',
        'hook',
        'script',
        'caption',
        'hashtags',
        'status',
        'assignee_id',
        'due_at',
        'scheduled_at',
        'published_at',
        'priority',
        'tags',
        'created_by',
    ];

    protected $casts = [
        'tags' => 'array',
        'due_at' => 'datetime',
        'scheduled_at' => 'datetime',
        'published_at' => 'datetime',
    ];

    public function workspace()
    {
        return $this->belongsTo(Workspace::class);
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
