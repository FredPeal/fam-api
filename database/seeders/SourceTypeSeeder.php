<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SourceTypeSeeder extends Seeder
{
    /**
     * Classification of where a transaction comes from.
     *
     * @var array<int, array{name: string, icon: string|null}>
     */
    public const array SOURCE_TYPES = [
        ['name' => 'manual', 'icon' => 'pencil'],
        ['name' => 'email', 'icon' => 'mail'],
        ['name' => 'bank_api', 'icon' => 'landmark'],
        ['name' => 'import', 'icon' => 'upload'],
        ['name' => 'scraper', 'icon' => 'globe'],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();

        $rows = array_map(
            fn (array $sourceType): array => [
                ...$sourceType,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            self::SOURCE_TYPES,
        );

        DB::table('source_types')->upsert($rows, ['name'], ['icon', 'updated_at']);
    }
}
