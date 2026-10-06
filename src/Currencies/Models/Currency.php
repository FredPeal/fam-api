<?php

declare(strict_types=1);

namespace Fam\Currencies\Models;

use Database\Factories\CurrencyFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[UseFactory(CurrencyFactory::class)]
class Currency extends Model
{
    /** @use HasFactory<CurrencyFactory> */
    use HasFactory;

    protected $table = 'currencies';

    protected $guarded = [];
}
