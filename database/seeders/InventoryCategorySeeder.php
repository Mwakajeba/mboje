<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Inventory\Category;
use App\Models\Company;

class InventoryCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            'Stationery and Office Supplies',
            'ICT and Electronics',
            'Furniture and Fixtures',
            'Cleaning and Sanitation',
            'Maintenance and Repairs',
            'Safety and Protective Gear',
            'Kitchen and Catering Supplies',
            'Medical Supplies',
            'Printing and Branding Materials',
            'Assets and Equipment',
        ];

        $company = Company::first();

        if ($company) {
            foreach ($categories as $index => $categoryName) {
                // Generate a simple code from the category name
                $code = strtoupper(substr(str_replace([' ', '&'], '', $categoryName), 0, 4)) . str_pad($index + 1, 3, '0', STR_PAD_LEFT);
                
                Category::firstOrCreate(
                    [
                        'name' => $categoryName,
                        'company_id' => $company->id,
                    ],
                    [
                        'code' => $code,
                        'description' => 'Category for ' . $categoryName,
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
