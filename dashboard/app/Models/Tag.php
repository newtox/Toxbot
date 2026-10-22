<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tag extends Model
{
    public const MAX_LENGTH = 2000;

    public const NAME_REGEX = '/^[a-z0-9_-]{1,32}$/';

    public const INVITE_REGEX = '~(discord\.gg|discord(app)?\.com/invite|dsc\.gg)/[A-Za-z0-9-]+~i';

    public const MASS_MENTION_REGEX = '/@(everyone|here)/';

    protected $fillable = ['name', 'owner', 'content', 'embed', 'embed_color', 'embed_image', 'uses'];

    protected function casts(): array
    {
        return ['embed' => 'boolean', 'uses' => 'integer'];
    }

    public function reports(): HasMany
    {
        return $this->hasMany(TagReport::class);
    }

    public static function contentProblem(string $content): ?string
    {
        return match (true) {
            trim($content) === '' => 'tags.content_empty',
            preg_match(self::INVITE_REGEX, $content) === 1 => 'tags.content_invite',
            preg_match(self::MASS_MENTION_REGEX, $content) === 1 => 'tags.content_mention',
            default => null,
        };
    }
}
