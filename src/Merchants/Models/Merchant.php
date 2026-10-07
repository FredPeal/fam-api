<?php

declare(strict_types=1);

namespace Fam\Merchants\Models;

use App\Models\User;
use Database\Factories\MerchantFactory;
use Fam\BaseModel;
use Fam\Categories\Models\Category;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[UseFactory(MerchantFactory::class)]
class Merchant extends BaseModel
{
    /** @use HasFactory<MerchantFactory> */
    use HasFactory;

    protected $table = 'merchants';

    protected $guarded = [];

    /**
     * Category the merchant belongs to. The merchant is owned by the category owner.
     *
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    /**
     * Merchants whose category is owned by the user.
     *
     * @param  Builder<Merchant>  $query
     * @return Builder<Merchant>
     */
    public function scopeOwnedBy(Builder $query, User $user): Builder
    {
        return $query->whereHas('category', fn (Builder $category) => $category->where('user_id', $user->id));
    }
}
