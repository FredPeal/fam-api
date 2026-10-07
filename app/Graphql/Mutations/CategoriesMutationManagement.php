<?php

declare(strict_types=1);

namespace App\Graphql\Mutations;

use App\Models\User;
use Fam\Categories\Actions\CreateCategory;
use Fam\Categories\Actions\DeleteCategory;
use Fam\Categories\Actions\UpdateCategory;
use Fam\Categories\DataTransferObject\Category as CategoryDto;
use Fam\Categories\Models\Category;
use GraphQL\Error\Error;

final class CategoriesMutationManagement
{
    /**
     * @param  array{name: string, description?: string|null, icon?: string|null}  $args
     */
    public function create(mixed $root, array $args): Category
    {
        /** @var User $user */
        $user = auth()->user();

        return (new CreateCategory($this->categoryDto($user, $args)))->execute($args);
    }

    /**
     * @param  array{id: int|string, name: string, description?: string|null, icon?: string|null}  $args
     */
    public function update(mixed $root, array $args): Category
    {
        /** @var User $user */
        $user = auth()->user();
        $category = $this->ownedCategory($user, $args['id']);

        return (new UpdateCategory($category, $this->categoryDto($user, $args)))->execute($args);
    }

    /**
     * @param  array{id: int|string}  $args
     */
    public function delete(mixed $root, array $args): bool
    {
        /** @var User $user */
        $user = auth()->user();
        $category = $this->ownedCategory($user, $args['id']);

        return (new DeleteCategory($category))->execute($args);
    }

    /**
     * @param  array{name: string, description?: string|null, icon?: string|null}  $args
     */
    private function categoryDto(User $user, array $args): CategoryDto
    {
        return new CategoryDto(
            user: $user,
            name: $args['name'],
            description: $args['description'] ?? null,
            icon: $args['icon'] ?? null,
        );
    }

    private function ownedCategory(User $user, int|string $id): Category
    {
        return Category::query()
            ->ownedBy($user)
            ->findOr($id, fn () => throw new Error('Category not found.'));
    }
}
