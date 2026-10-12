<?php

declare(strict_types=1);

namespace Fam\Transactions\DataTransferObject;

use App\Models\User;
use Carbon\CarbonInterface;
use Fam\Accounts\Models\Account;
use Fam\Categories\Models\Category;
use Fam\Currencies\Models\Currency;
use Fam\Merchants\Models\Merchant;
use Fam\Sources\Models\Source;
use Fam\Transactions\Enums\TransactionType;
use Spatie\LaravelData\Data;

class Transaction extends Data
{
    public function __construct(
        public User $user,
        public Account $account,
        public TransactionType $type,
        public float $amount,
        public Currency $currency,
        public CarbonInterface $occurredAt,
        public float $exchangeRate = 1.0,
        public ?Source $source = null,
        public ?Merchant $merchant = null,
        public ?Category $category = null,
        public ?string $description = null,
        public ?string $notes = null,
    ) {}

    /**
     * Amount as it affects the account, before the exchange rate.
     */
    public function amountSigned(): float
    {
        return round($this->type->signedAmount($this->amount), 2);
    }

    /**
     * Amount the transaction adds to (or removes from) the account balance, in the account currency.
     */
    public function accountBalanceDelta(): float
    {
        return round($this->amountSigned() * $this->exchangeRate, 2);
    }
}
