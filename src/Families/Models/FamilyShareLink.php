<?php

declare(strict_types=1);

namespace Fam\Families\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class FamilyShareLink extends Model
{
    protected $table = 'families_share_links';

    protected $guarded = [];

    /**
     * @return BelongsTo<Family, $this>
     */
    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class, 'families_id');
    }

    public static function generateUniqueCode(): string
    {
        do {
            $code = Str::random(32);
        } while (static::query()->where('code', $code)->exists());

        return $code;
    }

    public static function fullLinkFor(string $code): string
    {
        $baseUrl = rtrim((string) config('services.frontend.url'), '/');
        $path = '/'.trim((string) config('services.frontend.family_invitation_path'), '/');

        return $baseUrl.$path.'/'.$code;
    }
}
