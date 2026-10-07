<?php

declare(strict_types=1);

namespace Fam\Categories\Actions;

use Fam\Categories\DataTransferObject\Category as CategoryDto;
use Fam\Categories\Exceptions\CategoryException;
use Fam\Categories\Models\Category;
use Fam\Contracts\Interfaces\ActionInterface;
use Override;

class CreateCategory implements ActionInterface
{
    public function __construct(public CategoryDto $categoryDto) {}

    /**
     * Create the category. A user cannot have two categories with the same name.
     */
    #[Override]
    public function execute(array $params): Category
    {
        $nameTaken = Category::query()
            ->ownedBy($this->categoryDto->user)
            ->where('name', $this->categoryDto->name)
            ->exists();

        if ($nameTaken) {
            throw CategoryException::duplicateName($this->categoryDto->name);
        }

        return Category::create([
            'user_id' => $this->categoryDto->user->id,
            'name' => $this->categoryDto->name,
            'description' => $this->categoryDto->description,
            'icon' => $this->categoryDto->icon,
        ]);
    }
}
