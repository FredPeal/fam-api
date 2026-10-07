<?php

declare(strict_types=1);

namespace Fam\Categories\Models;

use App\Models\User;
use Database\Factories\FamilyCategoryFactory;
use Fam\Families\Models\Family;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Link that shares a category with a family.
 */
#[UseFactory(FamilyCategoryFactory::class)]
class FamilyCategory extends Model
{
    /** @use HasFactory<FamilyCategoryFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $table = 'families_categories';

    protected $guarded = [];

    /**
     * @return BelongsTo<Family, $this>
     */
    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class, 'families_id');
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'categories_id');
    }

    /**
     * Links the user may remove: those on a family they own or those sharing a category they own.
     *
     * @param  Builder<FamilyCategory>  $query
     * @return Builder<FamilyCategory>
     */
    public function scopeManageableBy(Builder $query, User $user): Builder
    {
        return $query->where(function (Builder $query) use ($user): void {
            $query
                ->whereHas('family', fn (Builder $family) => $family->where('user_id', $user->id))
                ->orWhereHas('category', fn (Builder $category) => $category->where('user_id', $user->id));
        });
    }
}
