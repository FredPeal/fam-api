<?php

declare(strict_types=1);

namespace App\Graphql\Queries;

use App\Models\User;
use Fam\Categories\Models\Category;
use GraphQL\Error\Error;
use Illuminate\Database\Eloquent\Collection;

final class CategoriesQueries
{
    /**
     * List every category owned by the authenticated user.
     *
     * @param  array{}  $args
     * @return Collection<int, Category>
     */
    public function categories(mixed $root, array $args): Collection
    {
        /** @var User $user */
        $user = auth()->user();

        return Category::query()
            ->ownedBy($user)
            ->orderBy('name')
            ->get();
    }

    /**
     * Find a single category owned by the authenticated user.
     *
     * @param  array{id: int|string}  $args
     */
    public function category(mixed $root, array $args): Category
    {
        /** @var User $user */
        $user = auth()->user();

        return Category::query()
            ->ownedBy($user)
            ->findOr($args['id'], fn () => throw new Error('Category not found.'));
    }
}
