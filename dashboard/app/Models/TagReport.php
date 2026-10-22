<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TagReport extends Model
{
    protected $fillable = ['tag_id', 'reporter', 'guild', 'reason', 'resolved'];

    protected function casts(): array
    {
        return ['resolved' => 'boolean'];
    }

    public function tag(): BelongsTo
    {
        return $this->belongsTo(Tag::class);
    }
}
