<?php

namespace Database\Seeders;

use Fam\Families\Enums\MemberTypeName;
use Fam\Families\Models\MemberType;
use Illuminate\Database\Seeder;

class MemberTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (MemberTypeName::cases() as $memberTypeName) {
            MemberType::named($memberTypeName);
        }
    }
}
