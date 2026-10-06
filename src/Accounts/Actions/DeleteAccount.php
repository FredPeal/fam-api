<?php

declare(strict_types=1);

namespace Fam\Accounts\Actions;

use Fam\Accounts\Models\Account;
use Fam\Contracts\Interfaces\ActionInterface;
use Override;

class DeleteAccount implements ActionInterface
{
    public function __construct(public Account $account) {}

    #[Override]
    public function execute(array $params): bool
    {
        return (bool) $this->account->delete();
    }
}
