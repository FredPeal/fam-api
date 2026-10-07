<?php

declare(strict_types=1);

namespace App\Graphql\Queries;

use App\Models\User;
use Fam\Merchants\Models\Merchant;
use GraphQL\Error\Error;
use Illuminate\Database\Eloquent\Collection;

final class MerchantsQueries
{
    /**
     * List every merchant owned by the authenticated user, optionally limited to one category.
     *
     * @param  array{category_id?: int|string|null}  $args
     * @return Collection<int, Merchant>
     */
    public function merchants(mixed $root, array $args): Collection
    {
        /** @var User $user */
        $user = auth()->user();

        return Merchant::query()
            ->ownedBy($user)
            ->when(isset($args['category_id']), fn ($query) => $query->where('category_id', $args['category_id']))
            ->orderBy('name')
            ->get();
    }

    /**
     * Find a single merchant owned by the authenticated user.
     *
     * @param  array{id: int|string}  $args
     */
    public function merchant(mixed $root, array $args): Merchant
    {
        /** @var User $user */
        $user = auth()->user();

        return Merchant::query()
            ->ownedBy($user)
            ->findOr($args['id'], fn () => throw new Error('Merchant not found.'));
    }
}
