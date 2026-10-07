<?php

declare(strict_types=1);

namespace App\Graphql\Queries;

use App\Models\User;
use Carbon\CarbonInterface;
use Fam\Transactions\Models\Transaction;
use GraphQL\Error\Error;
use Illuminate\Database\Eloquent\Builder;

final class TransactionsQueries
{
    /**
     * Query the transactions owned by the authenticated user, newest first. Lighthouse paginates the result.
     *
     * @param  array{account_id?: int|string|null, category_id?: int|string|null, merchant_id?: int|string|null, type?: string|null, from?: CarbonInterface|null, to?: CarbonInterface|null}  $args
     * @return Builder<Transaction>
     */
    public function transactions(mixed $root, array $args): Builder
    {
        /** @var User $user */
        $user = auth()->user();

        return Transaction::query()
            ->ownedBy($user)
            ->when(isset($args['account_id']), fn (Builder $query) => $query->where('account_id', $args['account_id']))
            ->when(isset($args['category_id']), fn (Builder $query) => $query->where('category_id', $args['category_id']))
            ->when(isset($args['merchant_id']), fn (Builder $query) => $query->where('merchant_id', $args['merchant_id']))
            ->when(isset($args['type']), fn (Builder $query) => $query->where('type', $args['type']))
            ->when(isset($args['from']), fn (Builder $query) => $query->where('occurred_at', '>=', $args['from']))
            ->when(isset($args['to']), fn (Builder $query) => $query->where('occurred_at', '<=', $args['to']))
            ->orderByDesc('occurred_at')
            ->orderByDesc('id');
    }

    /**
     * Find a single transaction owned by the authenticated user.
     *
     * @param  array{id: int|string}  $args
     */
    public function transaction(mixed $root, array $args): Transaction
    {
        /** @var User $user */
        $user = auth()->user();

        return Transaction::query()
            ->ownedBy($user)
            ->findOr($args['id'], fn () => throw new Error('Transaction not found.'));
    }
}
