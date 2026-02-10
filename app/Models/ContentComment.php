<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContentComment extends Model
{
    protected $fillable = [
        'content_id',
        'user_id',
        'message',
    ];

    public function content()
    {
        return $this->belongsTo(Content::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
