<?php

declare(strict_types=1);

namespace Fam\Contracts\Interfaces;

interface ActionInterface
{
    public function execute(array $params);
}
