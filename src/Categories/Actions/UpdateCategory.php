<?php

declare(strict_types=1);

namespace Fam\Categories\Actions;

use Fam\Categories\DataTransferObject\Category as CategoryDto;
use Fam\Categories\Exceptions\CategoryException;
use Fam\Categories\Models\Category;
use Fam\Contracts\Interfaces\ActionInterface;
use Override;

class UpdateCategory implements ActionInterface
{
    public function __construct(
        public Category $category,
        public CategoryDto $categoryDto,
    ) {}

    /**
     * Update the category. The new name cannot collide with another category of the same user.
     */
    #[Override]
    public function execute(array $params): Category
    {
        $nameTaken = Category::query()
            ->ownedBy($this->categoryDto->user)
            ->where('name', $this->categoryDto->name)
            ->whereKeyNot($this->category->id)
            ->exists();

        if ($nameTaken) {
            throw CategoryException::duplicateName($this->categoryDto->name);
        }

        $this->category->update([
            'name' => $this->categoryDto->name,
            'description' => $this->categoryDto->description,
            'icon' => $this->categoryDto->icon,
        ]);

        return $this->category;
    }
}
