<?php

declare(strict_types=1);

namespace Fam\Categories\Actions;

use Fam\Categories\Exceptions\CategoryException;
use Fam\Categories\Models\Category;
use Fam\Categories\Models\FamilyCategory;
use Fam\Contracts\Interfaces\ActionInterface;
use Fam\Families\Models\Family;
use Override;

class CreateFamilyCategory implements ActionInterface
{
    public function __construct(
        public Family $family,
        public Category $category,
    ) {}

    /**
     * Share the category with the family. A link removed earlier is restored instead of duplicated.
     */
    #[Override]
    public function execute(array $params): FamilyCategory
    {
        $familyCategory = FamilyCategory::withTrashed()
            ->where('families_id', $this->family->id)
            ->where('categories_id', $this->category->id)
            ->first();

        if ($familyCategory === null) {
            return FamilyCategory::create([
                'families_id' => $this->family->id,
                'categories_id' => $this->category->id,
            ]);
        }

        if (! $familyCategory->trashed()) {
            throw CategoryException::alreadySharedWithFamily();
        }

        $familyCategory->restore();

        return $familyCategory;
    }
}
