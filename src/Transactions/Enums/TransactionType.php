<?php

declare(strict_types=1);

namespace Fam\Transactions\Enums;

enum TransactionType: string
{
    case Income = 'income';
    case Expense = 'expense';
    case Transfer = 'transfer';
    case Adjustment = 'adjustment';

    /**
     * Amount as it affects the account. Income always adds and expense always subtracts, whatever
     * sign the client sent. Transfers and adjustments keep the sign the client chose.
     */
    public function signedAmount(float $amount): float
    {
        return match ($this) {
            self::Income => abs($amount),
            self::Expense => -abs($amount),
            self::Transfer, self::Adjustment => $amount,
        };
    }
}
