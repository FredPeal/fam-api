<?php

declare(strict_types=1);

namespace Fam\Families\Observers;

use Fam\Families\Actions\CreateFamilyShareLink;
use Fam\Families\Models\Family;

class FamilyObserver
{
    public function created(Family $family): void
    {
        (new CreateFamilyShareLink($family))->execute([]);
    }
}
