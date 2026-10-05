<?php

declare(strict_types=1);

namespace App\Graphql\Queries;

use App\Models\User;
use Fam\Families\Models\Family;
use GraphQL\Error\Error;
use Illuminate\Database\Eloquent\Collection;

final class FamiliesQueries
{
    /**
     * List every family owned by the authenticated user.
     *
     * @param  array{}  $args
     * @return Collection<int, Family>
     */
    public function families(mixed $root, array $args): Collection
    {
        /** @var User $user */
        $user = auth()->user();

        return Family::query()
            ->ownedBy($user)
            ->orderBy('name')
            ->get();
    }

    /**
     * Find a single family owned by the authenticated user.
     *
     * @param  array{id: int|string}  $args
     */
    public function family(mixed $root, array $args): Family
    {
        /** @var User $user */
        $user = auth()->user();

        return Family::query()
            ->ownedBy($user)
            ->findOr($args['id'], fn () => throw new Error('Family not found.'));
    }
}
