<?php

declare(strict_types=1);

namespace Fam\Families\Actions;

use Fam\Contracts\Interfaces\ActionInterface;
use Fam\Families\Models\Family;
use Override;

class DeleteFamily implements ActionInterface
{
    public function __construct(public Family $family) {}

    #[Override]
    public function execute(array $params): bool
    {
        return (bool) $this->family->delete();
    }
}
