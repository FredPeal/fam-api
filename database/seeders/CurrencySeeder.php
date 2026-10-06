<?php

namespace Database\Seeders;

use Fam\Currencies\Models\Currency;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    /**
     * Seed every currency known to the money package.
     */
    public function run(): void
    {
        foreach (config('money.currencies') as $code => $currency) {
            Currency::query()->updateOrCreate(
                ['code' => $code],
                ['name' => $currency['name']],
            );
        }
    }
}
