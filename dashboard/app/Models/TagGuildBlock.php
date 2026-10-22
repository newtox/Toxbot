<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TagGuildBlock extends Model
{
    public $timestamps = false;

    protected $fillable = ['guild', 'tag_id'];

    public function tag(): BelongsTo
    {
        return $this->belongsTo(Tag::class);
    }
}
