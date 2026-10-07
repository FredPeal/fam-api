<?php

declare(strict_types=1);

namespace Fam\Transactions\Actions;

use Fam\Accounts\Models\Account;
use Fam\Contracts\Interfaces\ActionInterface;
use Fam\Transactions\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Override;

class DeleteTransaction implements ActionInterface
{
    public function __construct(public Transaction $transaction) {}

    /**
     * Delete the transaction and revert its amount from the account balance.
     */
    #[Override]
    public function execute(array $params): bool
    {
        return DB::transaction(function (): bool {
            $account = Account::query()->lockForUpdate()->findOrFail($this->transaction->account_id);
            $account->update([
                'current_balance' => round((float) $account->current_balance - $this->transaction->accountBalanceDelta(), 2),
            ]);

            return (bool) $this->transaction->delete();
        });
    }
}
