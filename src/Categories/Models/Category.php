<?php

declare(strict_types=1);

namespace Fam\Categories\Models;

use App\Models\User;
use Database\Factories\CategoryFactory;
use Fam\BaseModel;
use Fam\Families\Models\Family;
use Fam\Merchants\Models\Merchant;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[UseFactory(CategoryFactory::class)]
class Category extends BaseModel
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory;

    protected $table = 'categories';

    protected $guarded = [];

    /**
     * Families the category is currently shared with.
     *
     * @return BelongsToMany<Family, $this>
     */
    public function families(): BelongsToMany
    {
        return $this->belongsToMany(Family::class, 'families_categories', 'categories_id', 'families_id')
            ->withTimestamps()
            ->wherePivotNull('deleted_at');
    }

    /**
     * @return HasMany<FamilyCategory, $this>
     */
    public function familyCategories(): HasMany
    {
        return $this->hasMany(FamilyCategory::class, 'categories_id');
    }

    /**
     * @return HasMany<Merchant, $this>
     */
    public function merchants(): HasMany
    {
        return $this->hasMany(Merchant::class, 'category_id');
    }

    /**
     * @param  Builder<Category>  $query
     * @return Builder<Category>
     */
    public function scopeOwnedBy(Builder $query, User $user): Builder
    {
        return $query->where('user_id', $user->id);
    }
}
