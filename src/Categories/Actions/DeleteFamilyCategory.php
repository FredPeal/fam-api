<?php

declare(strict_types=1);

namespace Fam\Categories\Actions;

use Fam\Categories\Models\FamilyCategory;
use Fam\Contracts\Interfaces\ActionInterface;
use Override;

class DeleteFamilyCategory implements ActionInterface
{
    public function __construct(public FamilyCategory $familyCategory) {}

    /**
     * Stop sharing the category with the family. The link is soft deleted so it can be restored.
     */
    #[Override]
    public function execute(array $params): bool
    {
        return (bool) $this->familyCategory->delete();
    }
}
