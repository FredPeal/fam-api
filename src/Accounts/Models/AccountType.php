<?php

declare(strict_types=1);

namespace Fam\Accounts\Models;

use Fam\Accounts\Enums\AccountTypeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountType extends Model
{
    protected $table = 'account_types';

    protected $guarded = [];

    /**
     * @return HasMany<Account, $this>
     */
    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class, 'account_type_id');
    }

    /**
     * Find the row for the enum case, creating it if the table was never seeded.
     */
    public static function fromEnum(AccountTypeId $accountTypeId): self
    {
        return static::query()->firstOrCreate(
            ['id' => $accountTypeId->value],
            ['name' => $accountTypeId->label()],
        );
    }
}
