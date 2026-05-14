<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Branch;
use App\Models\InventoryLocation;

class InventoryLocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all branches
        $branches = Branch::all();

        foreach ($branches as $branch) {
            // Create default locations for each branch
            $defaultLocations = [
                'Main Warehouse',
                'Storage Room',
                'Showroom',
                'Office Storage'
            ];

            foreach ($defaultLocations as $locationName) {
                InventoryLocation::firstOrCreate([
                    'branch_id' => $branch->id,
                    'name' => $locationName,
                ], [
                    'description' => 'Default ' . $locationName . ' for ' . $branch->name,
                    'is_active' => true,
                    'company_id' => $branch->company_id,
                    'created_by' => 1, // Default user ID
                ]);
            }
        }
    }
}
