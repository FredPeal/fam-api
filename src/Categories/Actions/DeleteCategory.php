<?php

declare(strict_types=1);

namespace Fam\Categories\Actions;

use Fam\Categories\Exceptions\CategoryException;
use Fam\Categories\Models\Category;
use Fam\Contracts\Interfaces\ActionInterface;
use Override;

class DeleteCategory implements ActionInterface
{
    public function __construct(public Category $category) {}

    /**
     * Delete the category. A category that still has merchants cannot be deleted.
     */
    #[Override]
    public function execute(array $params): bool
    {
        if ($this->category->merchants()->exists()) {
            throw CategoryException::hasMerchants();
        }

        return (bool) $this->category->delete();
    }
}
