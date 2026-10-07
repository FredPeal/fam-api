<?php

declare(strict_types=1);

namespace Fam\Merchants\Actions;

use Fam\Contracts\Interfaces\ActionInterface;
use Fam\Merchants\Models\Merchant;
use Override;

class DeleteMerchant implements ActionInterface
{
    public function __construct(public Merchant $merchant) {}

    /**
     * Delete the merchant. Transactions that referenced it keep existing without a merchant.
     */
    #[Override]
    public function execute(array $params): bool
    {
        return (bool) $this->merchant->delete();
    }
}
