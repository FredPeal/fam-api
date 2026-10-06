<?php

declare(strict_types=1);

namespace App\Graphql\Queries;

use Fam\Accounts\Models\AccountType;
use Illuminate\Database\Eloquent\Collection;

final class AccountTypesQueries
{
    /**
     * List the available account types.
     *
     * @param  array{}  $args
     * @return Collection<int, AccountType>
     */
    public function accountTypes(mixed $root, array $args): Collection
    {
        return AccountType::query()->orderBy('id')->get();
    }
}
