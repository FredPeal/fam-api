<?php

declare(strict_types=1);

namespace App\Graphql\Queries;

use App\Models\User;
use Fam\Accounts\Models\Account;
use GraphQL\Error\Error;
use Illuminate\Database\Eloquent\Collection;

final class AccountsQueries
{
    /**
     * List every account owned by the authenticated user.
     *
     * @param  array{}  $args
     * @return Collection<int, Account>
     */
    public function accounts(mixed $root, array $args): Collection
    {
        /** @var User $user */
        $user = auth()->user();

        return Account::query()
            ->ownedBy($user)
            ->orderBy('name')
            ->get();
    }

    /**
     * Find a single account owned by the authenticated user.
     *
     * @param  array{id: int|string}  $args
     */
    public function account(mixed $root, array $args): Account
    {
        /** @var User $user */
        $user = auth()->user();

        return Account::query()
            ->ownedBy($user)
            ->findOr($args['id'], fn () => throw new Error('Account not found.'));
    }
}
