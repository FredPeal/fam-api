<?php

declare(strict_types=1);

namespace App\Graphql\Queries;

use App\Models\User;
use Fam\Categories\Models\FamilyCategory;
use Fam\Families\Models\Family;
use GraphQL\Error\Error;
use Illuminate\Database\Eloquent\Collection;

final class FamilyCategoriesQueries
{
    /**
     * List the categories shared with a family the authenticated user owns or belongs to.
     *
     * @param  array{family_id: int|string}  $args
     * @return Collection<int, FamilyCategory>
     */
    public function familyCategories(mixed $root, array $args): Collection
    {
        /** @var User $user */
        $user = auth()->user();

        $family = Family::query()
            ->accessibleBy($user)
            ->findOr($args['family_id'], fn () => throw new Error('Family not found.'));

        return FamilyCategory::query()
            ->where('families_id', $family->id)
            ->with('category')
            ->get()
            ->sortBy(fn (FamilyCategory $familyCategory): string => $familyCategory->category->name)
            ->values();
    }
}
