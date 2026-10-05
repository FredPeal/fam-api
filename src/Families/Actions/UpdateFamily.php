<?php

declare(strict_types=1);

namespace Fam\Families\Actions;

use Fam\Contracts\Interfaces\ActionInterface;
use Fam\Families\DataTransferObject\Family as FamilyDto;
use Fam\Families\Models\Family;
use Override;

class UpdateFamily implements ActionInterface
{
    public function __construct(
        public Family $family,
        public FamilyDto $familyDto,
    ) {}

    #[Override]
    public function execute(array $params): Family
    {
        $this->family->update([
            'name' => $this->familyDto->name,
            'address' => $this->familyDto->address,
        ]);

        return $this->family;
    }
}
