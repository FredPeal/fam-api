<?php

declare(strict_types=1);

namespace Fam\Accounts\Actions;

use Fam\Accounts\DataTransferObject\Account as AccountDto;
use Fam\Accounts\Models\Account;
use Fam\Accounts\Models\AccountType;
use Fam\Contracts\Interfaces\ActionInterface;
use Override;

class UpdateAccount implements ActionInterface
{
    public function __construct(
        public Account $account,
        public AccountDto $accountDto,
    ) {}

    /**
     * Update the account. A change in the initial balance shifts the current balance by the same amount.
     */
    #[Override]
    public function execute(array $params): Account
    {
        $balanceDelta = round($this->accountDto->initialBalance - (float) $this->account->initial_balance, 2);

        $this->account->update([
            'account_type_id' => AccountType::fromEnum($this->accountDto->accountTypeId)->id,
            'currency_id' => $this->accountDto->currency->id,
            'name' => $this->accountDto->name,
            'description' => $this->accountDto->description,
            'icon' => $this->accountDto->icon,
            'initial_balance' => $this->accountDto->initialBalance,
            'current_balance' => round((float) $this->account->current_balance + $balanceDelta, 2),
        ]);

        return $this->account;
    }
}
