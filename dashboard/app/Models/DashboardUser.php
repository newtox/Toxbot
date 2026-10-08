<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Foundation\Auth\User as Authenticatable;

class DashboardUser extends Authenticatable
{
    protected $table = 'dashboard_users';

    public $incrementing = false;

    protected $keyType = 'int';

    protected $fillable = ['id', 'username', 'global_name', 'avatar'];

    protected $hidden = ['remember_token'];

    protected function displayName(): Attribute
    {
        return Attribute::get(fn () => $this->global_name ?: $this->username);
    }

    protected function avatarUrl(): Attribute
    {
        return Attribute::get(function () {
            if ($this->avatar) {
                $ext = str_starts_with($this->avatar, 'a_') ? 'gif' : 'png';

                return "https://cdn.discordapp.com/avatars/{$this->id}/{$this->avatar}.{$ext}?size=128";
            }

            $index = intdiv((int) $this->id, 4194304) % 6;

            return "https://cdn.discordapp.com/embed/avatars/{$index}.png";
        });
    }

    public function botSettings(): BotUser
    {
        return BotUser::firstOrNew(['id' => (string) $this->id], [
            'language' => BotUser::DEFAULT_LANGUAGE,
            'color' => BotUser::DEFAULT_COLOR,
        ]);
    }
}
