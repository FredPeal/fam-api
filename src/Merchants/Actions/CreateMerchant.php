<?php

declare(strict_types=1);

namespace Fam\Merchants\Actions;

use Fam\Contracts\Interfaces\ActionInterface;
use Fam\Merchants\DataTransferObject\Merchant as MerchantDto;
use Fam\Merchants\Exceptions\MerchantException;
use Fam\Merchants\Models\Merchant;
use Override;

class CreateMerchant implements ActionInterface
{
    public function __construct(public MerchantDto $merchantDto) {}

    /**
     * Create the merchant. The code is unique: a given code must be free and an omitted code is generated from the name.
     */
    #[Override]
    public function execute(array $params): Merchant
    {
        $code = $this->merchantDto->code;

        if ($code === null) {
            $code = (new GenerateMerchantCode($this->merchantDto->name))->execute($params);
        } elseif (Merchant::query()->where('code', $code)->exists()) {
            throw MerchantException::duplicateCode($code);
        }

        return Merchant::create([
            'category_id' => $this->merchantDto->category->id,
            'code' => $code,
            'name' => $this->merchantDto->name,
            'description' => $this->merchantDto->description,
        ]);
    }
}
