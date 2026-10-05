<?php

declare(strict_types=1);

namespace Fam\Families\Actions;

use Fam\Contracts\Interfaces\ActionInterface;
use Fam\Families\DataTransferObject\Family as FamilyDto;
use Fam\Families\Models\Family;
use Override;

class CreateFamily implements ActionInterface
{
    public function __construct(public FamilyDto $familyDto) {}

    #[Override]
    public function execute(array $params): Family
    {
        return Family::create([
            'user_id' => $this->familyDto->user->id,
            'name' => $this->familyDto->name,
            'address' => $this->familyDto->address,
        ]);
    }
}
