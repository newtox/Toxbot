<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketType extends Model
{
    public const MAX_PER_GUILD = 5;

    protected $fillable = ['guild', 'name', 'emoji', 'description', 'roles', 'position'];

    protected function casts(): array
    {
        return ['position' => 'integer'];
    }

    public function roleIds(): array
    {
        return array_values(array_filter(explode(',', (string) $this->roles)));
    }
}
