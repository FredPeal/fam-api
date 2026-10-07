<?php

declare(strict_types=1);

namespace Fam\Transactions\Actions;

use Fam\Accounts\Models\Account;
use Fam\Contracts\Interfaces\ActionInterface;
use Fam\Transactions\DataTransferObject\Transaction as TransactionDto;
use Fam\Transactions\Exceptions\TransactionException;
use Fam\Transactions\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Override;

class CreateTransaction implements ActionInterface
{
    public function __construct(public TransactionDto $transactionDto) {}

    /**
     * Create the transaction and apply its amount to the account balance. The balance the account
     * had right before is kept on the transaction as `last_balance_amount`.
     */
    #[Override]
    public function execute(array $params): Transaction
    {
        if ($this->transactionDto->amountSigned() === 0.0) {
            throw TransactionException::zeroAmount();
        }

        return DB::transaction(function (): Transaction {
            $account = Account::query()->lockForUpdate()->findOrFail($this->transactionDto->account->id);
            $balanceBefore = (float) $account->current_balance;

            $transaction = Transaction::create([
                'account_id' => $account->id,
                'user_id' => $this->transactionDto->user->id,
                'source_id' => $this->transactionDto->source?->id,
                'merchant_id' => $this->transactionDto->merchant?->id,
                'category_id' => $this->transactionDto->category?->id,
                'type' => $this->transactionDto->type->value,
                'amount' => abs($this->transactionDto->amount),
                'amount_signed' => $this->transactionDto->amountSigned(),
                'currency_id' => $this->transactionDto->currency->id,
                'exchange_rate' => $this->transactionDto->exchangeRate,
                'occurred_at' => $this->transactionDto->occurredAt,
                'last_balance_amount' => $balanceBefore,
                'description' => $this->transactionDto->description,
                'notes' => $this->transactionDto->notes,
            ]);

            $account->update([
                'current_balance' => round($balanceBefore + $this->transactionDto->accountBalanceDelta(), 2),
            ]);

            return $transaction;
        });
    }
}
