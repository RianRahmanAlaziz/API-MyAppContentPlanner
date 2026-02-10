<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContentApproval extends Model
{
    protected $fillable = [
        'content_id',
        'reviewer_id',
        'status',
        'note',
    ];

    public function content()
    {
        return $this->belongsTo(Content::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }
}
