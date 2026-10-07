<?php

declare(strict_types=1);

namespace Fam\Sources\Models;

use Database\Factories\SourceTypeFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[UseFactory(SourceTypeFactory::class)]
class SourceType extends Model
{
    /** @use HasFactory<SourceTypeFactory> */
    use HasFactory;

    protected $table = 'source_types';

    protected $guarded = [];
}
