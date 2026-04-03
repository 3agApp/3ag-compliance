<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Supplier::factory()
            ->count(50)
            ->has(Brand::factory()->count(fake()->numberBetween(0, 5)))
            ->create();
    }
}
