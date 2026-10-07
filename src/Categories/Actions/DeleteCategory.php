<?php

declare(strict_types=1);

namespace Fam\Categories\Actions;

use Fam\Categories\Models\Category;
use Fam\Contracts\Interfaces\ActionInterface;
use Override;

class DeleteCategory implements ActionInterface
{
    public function __construct(public Category $category) {}

    #[Override]
    public function execute(array $params): bool
    {
        return (bool) $this->category->delete();
    }
}
