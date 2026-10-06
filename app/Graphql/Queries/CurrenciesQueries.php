<?php

declare(strict_types=1);

namespace App\Graphql\Queries;

use Fam\Currencies\Models\Currency;
use Illuminate\Database\Eloquent\Collection;

final class CurrenciesQueries
{
    /**
     * List the currencies an account can use.
     *
     * @param  array{}  $args
     * @return Collection<int, Currency>
     */
    public function currencies(mixed $root, array $args): Collection
    {
        return Currency::query()->orderBy('code')->get();
    }
}
