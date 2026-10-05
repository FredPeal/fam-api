<?php

declare(strict_types=1);

namespace Fam\Families\Actions;

use Fam\Contracts\Interfaces\ActionInterface;
use Fam\Families\Models\Family;
use Fam\Families\Models\FamilyShareLink;
use Override;

class CreateFamilyShareLink implements ActionInterface
{
    public function __construct(public Family $family) {}

    #[Override]
    public function execute(array $params): FamilyShareLink
    {
        $code = FamilyShareLink::generateUniqueCode();

        return $this->family->shareLink()->create([
            'code' => $code,
            'full_link' => FamilyShareLink::fullLinkFor($code),
        ]);
    }
}
