<?php

declare(strict_types=1);

namespace Fam\Merchants\Actions;

use Fam\Contracts\Interfaces\ActionInterface;
use Fam\Merchants\Models\Merchant;
use Illuminate\Support\Str;
use Override;

class GenerateMerchantCode implements ActionInterface
{
    public function __construct(public string $name) {}

    /**
     * Build a unique code from the name: the slug of the name, suffixed with a counter when the slug is taken.
     */
    #[Override]
    public function execute(array $params): string
    {
        $base = Str::slug($this->name);

        if ($base === '') {
            $base = 'merchant';
        }

        $code = $base;
        $suffix = 2;

        while (Merchant::query()->where('code', $code)->exists()) {
            $code = "{$base}-{$suffix}";
            $suffix++;
        }

        return $code;
    }
}
