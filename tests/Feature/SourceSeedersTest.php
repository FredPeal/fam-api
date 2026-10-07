<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\SourceSeeder;
use Database\Seeders\SourceTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SourceSeedersTest extends TestCase
{
    use RefreshDatabase;

    public function test_source_type_seeder_creates_every_type_once_even_when_run_twice(): void
    {
        $this->seed(SourceTypeSeeder::class);
        $this->seed(SourceTypeSeeder::class);

        $this->assertDatabaseCount('source_types', count(SourceTypeSeeder::SOURCE_TYPES));

        foreach (SourceTypeSeeder::SOURCE_TYPES as $sourceType) {
            $this->assertDatabaseHas('source_types', ['name' => $sourceType['name']]);
        }
    }

    public function test_source_seeder_creates_sources_linked_to_their_type_and_user(): void
    {
        $user = User::factory()->create(['email' => SourceSeeder::USER_EMAIL]);

        $this->seed(SourceSeeder::class);
        $this->seed(SourceSeeder::class);

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('sources', count(SourceSeeder::SOURCES));

        foreach (SourceSeeder::SOURCES as $source) {
            $this->assertDatabaseHas('sources', [
                'user_id' => $user->id,
                'name' => $source['name'],
                'source_type_id' => $this->sourceTypeId($source['source_type']),
            ]);
        }
    }

    public function test_source_seeder_creates_the_owner_user_when_missing(): void
    {
        $this->seed(SourceSeeder::class);

        $this->assertDatabaseHas('users', ['email' => SourceSeeder::USER_EMAIL]);
        $this->assertDatabaseCount('sources', count(SourceSeeder::SOURCES));
    }

    private function sourceTypeId(string $name): int
    {
        return (int) DB::table('source_types')->where('name', $name)->value('id');
    }
}
