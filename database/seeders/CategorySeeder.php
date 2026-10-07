<?php

namespace Database\Seeders;

use App\Models\User;
use Fam\Categories\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Email of the user that owns the default expense categories.
     */
    public const string USER_EMAIL = 'test@example.com';

    /**
     * Default expense categories. Icons are lucide icon names.
     *
     * @var array<int, array{name: string, description: string, icon: string}>
     */
    public const array EXPENSE_CATEGORIES = [
        ['name' => 'Groceries', 'description' => 'Supermarket and food at home', 'icon' => 'shopping-cart'],
        ['name' => 'Dining Out', 'description' => 'Restaurants, cafes and delivery', 'icon' => 'utensils'],
        ['name' => 'Transportation', 'description' => 'Public transit, taxis and ride sharing', 'icon' => 'bus'],
        ['name' => 'Fuel', 'description' => 'Gas and vehicle charging', 'icon' => 'fuel'],
        ['name' => 'Vehicle', 'description' => 'Maintenance, parking and tolls', 'icon' => 'car'],
        ['name' => 'Housing', 'description' => 'Rent, mortgage and home maintenance', 'icon' => 'house'],
        ['name' => 'Utilities', 'description' => 'Electricity, water and gas', 'icon' => 'plug'],
        ['name' => 'Internet & Phone', 'description' => 'Internet, mobile and TV plans', 'icon' => 'wifi'],
        ['name' => 'Health', 'description' => 'Doctors, pharmacy and medical care', 'icon' => 'heart-pulse'],
        ['name' => 'Insurance', 'description' => 'Health, vehicle and home insurance', 'icon' => 'shield'],
        ['name' => 'Education', 'description' => 'Tuition, courses and books', 'icon' => 'graduation-cap'],
        ['name' => 'Entertainment', 'description' => 'Movies, events and hobbies', 'icon' => 'clapperboard'],
        ['name' => 'Subscriptions', 'description' => 'Streaming, software and memberships', 'icon' => 'repeat'],
        ['name' => 'Shopping', 'description' => 'General purchases and electronics', 'icon' => 'shopping-bag'],
        ['name' => 'Clothing', 'description' => 'Clothes, shoes and accessories', 'icon' => 'shirt'],
        ['name' => 'Personal Care', 'description' => 'Haircuts, cosmetics and wellness', 'icon' => 'sparkles'],
        ['name' => 'Travel', 'description' => 'Flights, lodging and vacations', 'icon' => 'plane'],
        ['name' => 'Gifts & Donations', 'description' => 'Presents and charity', 'icon' => 'gift'],
        ['name' => 'Pets', 'description' => 'Food, vet and pet supplies', 'icon' => 'paw-print'],
        ['name' => 'Kids', 'description' => 'Childcare, toys and school supplies', 'icon' => 'baby'],
        ['name' => 'Taxes & Fees', 'description' => 'Taxes, bank fees and commissions', 'icon' => 'receipt'],
        ['name' => 'Other Expenses', 'description' => 'Anything that does not fit elsewhere', 'icon' => 'ellipsis'],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::query()->firstWhere('email', self::USER_EMAIL)
            ?? User::factory()->create([
                'name' => 'Fre',
                'first_name' => 'Fre',
                'email' => self::USER_EMAIL,
            ]);

        foreach (self::EXPENSE_CATEGORIES as $category) {
            Category::query()->updateOrCreate(
                ['user_id' => $user->id, 'name' => $category['name']],
                ['description' => $category['description'], 'icon' => $category['icon']],
            );
        }
    }
}
