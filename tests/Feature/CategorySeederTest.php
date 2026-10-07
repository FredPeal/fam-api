<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategorySeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_seeder_creates_every_expense_category_for_the_owner_once_even_when_run_twice(): void
    {
        $user = User::factory()->create(['email' => CategorySeeder::USER_EMAIL]);

        $this->seed(CategorySeeder::class);
        $this->seed(CategorySeeder::class);

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('categories', count(CategorySeeder::EXPENSE_CATEGORIES));

        foreach (CategorySeeder::EXPENSE_CATEGORIES as $category) {
            $this->assertDatabaseHas('categories', [
                'user_id' => $user->id,
                'name' => $category['name'],
                'description' => $category['description'],
                'icon' => $category['icon'],
            ]);
        }
    }

    public function test_category_seeder_creates_the_owner_user_when_missing(): void
    {
        $this->seed(CategorySeeder::class);

        $this->assertDatabaseHas('users', ['email' => CategorySeeder::USER_EMAIL]);
        $this->assertDatabaseCount('categories', count(CategorySeeder::EXPENSE_CATEGORIES));
    }
}
