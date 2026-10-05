<?php

declare(strict_types=1);

namespace Fam\Families\Models;

use App\Models\User;
use Database\Factories\FamilyFactory;
use Fam\BaseModel;
use Fam\Families\Observers\FamilyObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[UseFactory(FamilyFactory::class)]
#[ObservedBy(FamilyObserver::class)]
class Family extends BaseModel
{
    /** @use HasFactory<FamilyFactory> */
    use HasFactory;

    use HasUuids;

    protected $table = 'families';

    protected $guarded = [];

    /**
     * Keep the auto-incrementing primary key and generate the uuid column instead.
     *
     * @return array<int, string>
     */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'family_members', 'family_id', 'user_id')
            ->withPivot(['member_type_id', 'invited_by'])
            ->withTimestamps();
    }

    /**
     * @return HasOne<FamilyShareLink, $this>
     */
    public function shareLink(): HasOne
    {
        return $this->hasOne(FamilyShareLink::class, 'families_id');
    }

    /**
     * @param  Builder<Family>  $query
     * @return Builder<Family>
     */
    public function scopeOwnedBy(Builder $query, User $user): Builder
    {
        return $query->where('user_id', $user->id);
    }
}
