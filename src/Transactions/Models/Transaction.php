<?php

declare(strict_types=1);

namespace Fam\Transactions\Models;

use App\Models\User;
use Database\Factories\TransactionFactory;
use Fam\Accounts\Models\Account;
use Fam\BaseModel;
use Fam\Categories\Models\Category;
use Fam\Currencies\Models\Currency;
use Fam\Merchants\Models\Merchant;
use Fam\Sources\Models\Source;
use Fam\Transactions\Enums\TransactionType;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[UseFactory(TransactionFactory::class)]
class Transaction extends BaseModel
{
    /** @use HasFactory<TransactionFactory> */
    use HasFactory;

    protected $table = 'transactions';

    protected $guarded = [];

    /**
     * The type is kept as its string value so GraphQL can map it through the TransactionType enum.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'amount_signed' => 'decimal:2',
            'exchange_rate' => 'decimal:6',
            'last_balance_amount' => 'decimal:2',
            'occurred_at' => 'datetime',
        ];
    }

    public function transactionType(): TransactionType
    {
        return TransactionType::from($this->type);
    }

    /**
     * Amount the transaction added to (or removed from) its account balance, in the account currency.
     */
    public function accountBalanceDelta(): float
    {
        return round((float) $this->amount_signed * (float) $this->exchange_rate, 2);
    }

    /**
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    /**
     * @return BelongsTo<Currency, $this>
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    /**
     * @return BelongsTo<Source, $this>
     */
    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class, 'source_id');
    }

    /**
     * @return BelongsTo<Merchant, $this>
     */
    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class, 'merchant_id');
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    /**
     * @param  Builder<Transaction>  $query
     * @return Builder<Transaction>
     */
    public function scopeOwnedBy(Builder $query, User $user): Builder
    {
        return $query->where('user_id', $user->id);
    }
}
