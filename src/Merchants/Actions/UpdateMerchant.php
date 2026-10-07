<?php

declare(strict_types=1);

namespace Fam\Merchants\Actions;

use Fam\Contracts\Interfaces\ActionInterface;
use Fam\Merchants\DataTransferObject\Merchant as MerchantDto;
use Fam\Merchants\Exceptions\MerchantException;
use Fam\Merchants\Models\Merchant;
use Override;

class UpdateMerchant implements ActionInterface
{
    public function __construct(
        public Merchant $merchant,
        public MerchantDto $merchantDto,
    ) {}

    /**
     * Update the merchant. An omitted code keeps the current one; a given code cannot collide with another merchant.
     */
    #[Override]
    public function execute(array $params): Merchant
    {
        $code = $this->merchantDto->code ?? $this->merchant->code;

        $codeTaken = Merchant::query()
            ->where('code', $code)
            ->whereKeyNot($this->merchant->id)
            ->exists();

        if ($codeTaken) {
            throw MerchantException::duplicateCode($code);
        }

        $this->merchant->update([
            'category_id' => $this->merchantDto->category->id,
            'code' => $code,
            'name' => $this->merchantDto->name,
            'description' => $this->merchantDto->description,
        ]);

        return $this->merchant;
    }
}
