<?php

declare(strict_types=1);

namespace Fam\Accounts\Actions;

use Fam\Accounts\DataTransferObject\Account as AccountDto;
use Fam\Accounts\Models\Account;
use Fam\Accounts\Models\AccountType;
use Fam\Contracts\Interfaces\ActionInterface;
use Override;

class CreateAccount implements ActionInterface
{
    public function __construct(public AccountDto $accountDto) {}

    /**
     * Create the account with its current balance equal to the initial balance.
     */
    #[Override]
    public function execute(array $params): Account
    {
        return Account::create([
            'user_id' => $this->accountDto->user->id,
            'account_type_id' => AccountType::fromEnum($this->accountDto->accountTypeId)->id,
            'currency_id' => $this->accountDto->currency->id,
            'name' => $this->accountDto->name,
            'description' => $this->accountDto->description,
            'icon' => $this->accountDto->icon,
            'initial_balance' => $this->accountDto->initialBalance,
            'current_balance' => $this->accountDto->initialBalance,
        ]);
    }
}
