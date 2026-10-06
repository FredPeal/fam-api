<?php

declare(strict_types=1);

namespace Fam\Accounts\Enums;

/**
 * Primary keys of the `account_types` table. The seeder writes these exact ids.
 */
enum AccountTypeId: int
{
    case Cash = 1;
    case Bank = 2;
    case Savings = 3;
    case CreditCard = 4;
    case Investment = 5;
    case Loan = 6;
    case DigitalWallet = 7;

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Cash',
            self::Bank => 'Bank Account',
            self::Savings => 'Savings Account',
            self::CreditCard => 'Credit Card',
            self::Investment => 'Investment',
            self::Loan => 'Loan',
            self::DigitalWallet => 'Digital Wallet',
        };
    }
}
