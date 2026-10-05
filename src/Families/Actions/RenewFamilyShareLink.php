<?php

declare(strict_types=1);

namespace Fam\Families\Actions;

use Fam\Contracts\Interfaces\ActionInterface;
use Fam\Families\Models\Family;
use Fam\Families\Models\FamilyShareLink;
use Override;

class RenewFamilyShareLink implements ActionInterface
{
    public function __construct(public Family $family) {}

    /**
     * Replace the invitation code so previously shared links stop working.
     */
    #[Override]
    public function execute(array $params): FamilyShareLink
    {
        $shareLink = $this->family->shareLink;

        if ($shareLink === null) {
            return (new CreateFamilyShareLink($this->family))->execute($params);
        }

        $code = FamilyShareLink::generateUniqueCode();

        $shareLink->update([
            'code' => $code,
            'full_link' => FamilyShareLink::fullLinkFor($code),
        ]);

        return $shareLink;
    }
}
