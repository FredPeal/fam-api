<?php

namespace Database\Seeders;

use Fam\Accounts\Enums\AccountTypeId;
use Fam\Accounts\Models\AccountType;
use Illuminate\Database\Seeder;

class AccountTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (AccountTypeId::cases() as $accountTypeId) {
            AccountType::query()->updateOrCreate(
                ['id' => $accountTypeId->value],
                ['name' => $accountTypeId->label()],
            );
        }
    }
}
