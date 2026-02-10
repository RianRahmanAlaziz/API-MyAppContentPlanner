<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContentChecklistItem extends Model
{
    protected $fillable = [
        'content_id',
        'label',
        'is_done',
        'sort_order',
    ];

    protected $casts = [
        'is_done' => 'boolean',
    ];

    public function content()
    {
        return $this->belongsTo(Content::class);
    }
}
