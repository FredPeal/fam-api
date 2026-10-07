<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SourceSeeder extends Seeder
{
    /**
     * Email of the user that owns the example sources.
     */
    public const string USER_EMAIL = 'test@example.com';

    /**
     * Concrete source instances, keyed by the source type they belong to.
     *
     * @var array<int, array{source_type: string, name: string, icon: string|null}>
     */
    public const array SOURCES = [
        ['source_type' => 'email', 'name' => 'Gmail Personal', 'icon' => 'mail'],
        ['source_type' => 'bank_api', 'name' => 'Banco Popular', 'icon' => 'landmark'],
        ['source_type' => 'manual', 'name' => 'Manual', 'icon' => 'pencil'],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call(SourceTypeSeeder::class);

        $user = User::query()->firstWhere('email', self::USER_EMAIL)
            ?? User::factory()->create([
                'name' => 'Fre',
                'first_name' => 'Fre',
                'email' => self::USER_EMAIL,
            ]);

        $sourceTypeIds = DB::table('source_types')->pluck('id', 'name');
        $now = now();

        foreach (self::SOURCES as $source) {
            DB::table('sources')->updateOrInsert(
                [
                    'user_id' => $user->id,
                    'source_type_id' => $sourceTypeIds[$source['source_type']],
                    'name' => $source['name'],
                ],
                [
                    'icon' => $source['icon'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            );
        }
    }
}
