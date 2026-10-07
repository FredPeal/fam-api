<?php

declare(strict_types=1);

namespace App\Graphql\Mutations;

use App\Models\User;
use Fam\Categories\Actions\CreateFamilyCategory;
use Fam\Categories\Actions\DeleteFamilyCategory;
use Fam\Categories\Models\Category;
use Fam\Categories\Models\FamilyCategory;
use Fam\Families\Models\Family;
use GraphQL\Error\Error;

final class FamilyCategoriesMutationManagement
{
    /**
     * Share a category owned by the authenticated user with a family they own or belong to.
     *
     * @param  array{family_id: int|string, category_id: int|string}  $args
     */
    public function create(mixed $root, array $args): FamilyCategory
    {
        /** @var User $user */
        $user = auth()->user();

        $family = Family::query()
            ->accessibleBy($user)
            ->findOr($args['family_id'], fn () => throw new Error('Family not found.'));

        $category = Category::query()
            ->ownedBy($user)
            ->findOr($args['category_id'], fn () => throw new Error('Category not found.'));

        return (new CreateFamilyCategory($family, $category))->execute($args);
    }

    /**
     * Stop sharing a category. Allowed to the family owner and to the category owner.
     *
     * @param  array{id: int|string}  $args
     */
    public function delete(mixed $root, array $args): bool
    {
        /** @var User $user */
        $user = auth()->user();

        $familyCategory = FamilyCategory::query()
            ->manageableBy($user)
            ->findOr($args['id'], fn () => throw new Error('Family category not found.'));

        return (new DeleteFamilyCategory($familyCategory))->execute($args);
    }
}
