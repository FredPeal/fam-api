<?php

declare(strict_types=1);

namespace Fam\Sources\Models;

use App\Models\User;
use Database\Factories\SourceFactory;
use Fam\BaseModel;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[UseFactory(SourceFactory::class)]
class Source extends BaseModel
{
    /** @use HasFactory<SourceFactory> */
    use HasFactory;

    protected $table = 'sources';

    protected $guarded = [];

    /**
     * @return BelongsTo<SourceType, $this>
     */
    public function sourceType(): BelongsTo
    {
        return $this->belongsTo(SourceType::class, 'source_type_id');
    }

    /**
     * @param  Builder<Source>  $query
     * @return Builder<Source>
     */
    public function scopeOwnedBy(Builder $query, User $user): Builder
    {
        return $query->where('user_id', $user->id);
    }
}
