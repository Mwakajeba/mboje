<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Inventory\Category;
use App\Models\Inventory\Item;
use App\Models\User;

class TestInventoryDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        // Reference an existing user (seeded via UserSeeder)
        $user = User::first();
    $companyId = 1; // Set your default company ID or fetch as needed

        // Create inventory categories
        $categories = [
            ['name' => 'Electronics', 'description' => 'Electronic items and gadgets'],
            ['name' => 'Office Supplies', 'description' => 'Office and stationery items'],
            ['name' => 'Beverages', 'description' => 'Drinks and beverages'],
        ];

        foreach ($categories as $categoryData) {
            $category = Category::firstOrCreate(
                ['name' => $categoryData['name']],
                array_merge($categoryData, [
                    'company_id' => $companyId,
                    'code' => strtoupper(substr($categoryData['name'], 0, 3)),
                    'is_active' => true,
                ])
            );

            // Create items for each category
            if ($category->name === 'Electronics') {
                $this->createElectronicsItems($category, $user, $companyId);
            } elseif ($category->name === 'Office Supplies') {
                $this->createOfficeSuppliesItems($category, $user, $companyId);
            } elseif ($category->name === 'Beverages') {
                $this->createBeverageItems($category, $user, $companyId);
            }
        }

        $this->command->info('Test inventory data seeded successfully!');
    }

    private function createElectronicsItems($category, $user, $companyId)
    {
        $items = [
            [
                'name' => 'Laptop',
                'code' => 'LAP001',
                'description' => 'High performance laptop',
                'unit_price' => 2500000.00, 
                'cost_price' => 2000000.00,
                'unit_of_measure' => 'piece',
                'minimum_stock' => 5,
            ],
            [
                'name' => 'Mouse',
                'code' => 'MOU001',
                'description' => 'Wireless mouse',
                'unit_price' => 15000.00,
                'cost_price' => 10000.00,
                'unit_of_measure' => 'piece',
                'minimum_stock' => 20,
            ],
            [
                'name' => 'Keyboard',
                'code' => 'KEY001',
                'description' => 'Mechanical keyboard',
                'unit_price' => 45000.00,
                'cost_price' => 30000.00,
                'unit_of_measure' => 'piece',
                'minimum_stock' => 10,
            ],
            [
                'name' => 'USB Flash Drive',
                'code' => 'USB001',
                'description' => '32GB USB flash drive',
                'unit_price' => 25000.00,
                'cost_price' => 15000.00,
                'unit_of_measure' => 'piece',
                'minimum_stock' => 30,
            ],
            [
                'name' => 'Monitor',
                'code' => 'MON001',
                'description' => '24-inch LED monitor',
                'unit_price' => 180000.00,
                'cost_price' => 140000.00,
                'unit_of_measure' => 'piece',
                'minimum_stock' => 8,
            ],
            [
                'name' => 'Headphones',
                'code' => 'HEAD001',
                'description' => 'Wireless Bluetooth headphones',
                'unit_price' => 35000.00,
                'cost_price' => 25000.00,
                'unit_of_measure' => 'piece',
                'minimum_stock' => 15,
            ],
            [
                'name' => 'Webcam',
                'code' => 'WEB001',
                'description' => 'HD webcam for video calls',
                'unit_price' => 28000.00,
                'cost_price' => 20000.00,
                'unit_of_measure' => 'piece',
                'minimum_stock' => 12,
            ],
        ];

        foreach ($items as $itemData) {
            $item = Item::firstOrCreate(
                ['code' => $itemData['code']],
                array_merge($itemData, [
                    'company_id' => $companyId,
                    'category_id' => $category->id,
                    'is_active' => true,
                    'is_service' => false,
                ])
            );

            $originalQuantity = 0;
            
            // Optionally seed stock for a single default location
            $location = \App\Models\InventoryLocation::orderBy('id')->first();
            if ($location) {
                \App\Models\Inventory\StockLevel::updateOrCreate(
                    [
                        'item_id' => $item->id,
                        'inventory_location_id' => $location->id,
                    ],
                    [
                        'quantity' => $originalQuantity,
                    ]
                );
            }
        }
    }

    private function createOfficeSuppliesItems($category, $user, $companyId)
    {
        $items = [
            [
                'name' => 'Notebook',
                'code' => 'NOTE001',
                'description' => 'A4 size notebook',
                'unit_price' => 2500.00,
                'cost_price' => 1800.00,
                'unit_of_measure' => 'piece',
                'minimum_stock' => 50,
            ],
            [
                'name' => 'Pen',
                'code' => 'PEN001',
                'description' => 'Blue ballpoint pen',
                'unit_price' => 500.00,
                'cost_price' => 300.00,
                'unit_of_measure' => 'piece',
                'minimum_stock' => 100,
            ],
            [
                'name' => 'Stapler',
                'code' => 'STAP001',
                'description' => 'Office stapler',
                'unit_price' => 8000.00,
                'cost_price' => 5500.00,
                'unit_of_measure' => 'piece',
                'minimum_stock' => 10,
            ],
            [
                'name' => 'Paper A4',
                'code' => 'PAP001',
                'description' => 'A4 printing paper (500 sheets)',
                'unit_price' => 12000.00,
                'cost_price' => 8500.00,
                'unit_of_measure' => 'ream',
                'minimum_stock' => 20,
            ],
            [
                'name' => 'Calculator',
                'code' => 'CALC001',
                'description' => 'Scientific calculator',
                'unit_price' => 15000.00,
                'cost_price' => 11000.00,
                'unit_of_measure' => 'piece',
                'minimum_stock' => 15,
            ],
            [
                'name' => 'File Folder',
                'code' => 'FILE001',
                'description' => 'Manila file folder',
                'unit_price' => 800.00,
                'cost_price' => 500.00,
                'unit_of_measure' => 'piece',
                'minimum_stock' => 80,
            ],
            [
                'name' => 'Sticky Notes',
                'code' => 'STICK001',
                'description' => 'Post-it notes (100 sheets)',
                'unit_price' => 1200.00,
                'cost_price' => 800.00,
                'unit_of_measure' => 'pack',
                'minimum_stock' => 40,
            ],
        ];

        foreach ($items as $itemData) {
            $item = Item::firstOrCreate(
                ['code' => $itemData['code']],
                array_merge($itemData, [
                    'company_id' => $companyId,
                    'category_id' => $category->id,
                    'is_active' => true,
                    'is_service' => false,
                ])
            );
            // Optionally seed stock for a single default location
            $location = \App\Models\InventoryLocation::orderBy('id')->first();
            $originalQuantity = 0;
            if ($location) {
                \App\Models\Inventory\StockLevel::updateOrCreate(
                    [
                        'item_id' => $item->id,
                        'inventory_location_id' => $location->id,
                    ],
                    [
                        'quantity' => $originalQuantity,
                    ]
                );
            }
        }
    }

    private function createBeverageItems($category, $user, $companyId)
    {
        $items = [
            [
                'name' => 'Coffee',
                'code' => 'COF001',
                'description' => 'Premium coffee beans (1kg)',
                'unit_price' => 8000.00,
                'cost_price' => 5500.00,
                'unit_of_measure' => 'kg',
                'minimum_stock' => 20,
            ],
            [
                'name' => 'Tea',
                'code' => 'TEA001',
                'description' => 'Black tea leaves (500g)',
                'unit_price' => 3500.00,
                'cost_price' => 2500.00,
                'unit_of_measure' => 'packet',
                'minimum_stock' => 15,
            ],
            [
                'name' => 'Juice',
                'code' => 'JUI001',
                'description' => 'Orange juice 1L',
                'unit_price' => 2500.00,
                'cost_price' => 1800.00,
                'unit_of_measure' => 'bottle',
                'minimum_stock' => 30,
            ],
            [
                'name' => 'Soda',
                'code' => 'SODA001',
                'description' => 'Coca Cola 500ml',
                'unit_price' => 1000.00,
                'cost_price' => 700.00,
                'unit_of_measure' => 'bottle',
                'minimum_stock' => 50,
            ],
            [
                'name' => 'Water',
                'code' => 'WAT001',
                'description' => 'Mineral water 500ml',
                'unit_price' => 500.00,
                'cost_price' => 300.00,
                'unit_of_measure' => 'bottle',
                'minimum_stock' => 100,
            ],
            [
                'name' => 'Milk',
                'code' => 'MILK001',
                'description' => 'Fresh milk 1L',
                'unit_price' => 1800.00,
                'cost_price' => 1200.00,
                'unit_of_measure' => 'bottle',
                'minimum_stock' => 25,
            ],
        ];

        foreach ($items as $itemData) {
            $item = Item::firstOrCreate(
                ['code' => $itemData['code']],
                array_merge($itemData, [
                    'company_id' => $companyId,
                    'category_id' => $category->id,
                    'is_active' => true,
                    'is_service' => false,
                ])
            );
            // Optionally seed stock for a single default location
            $location = \App\Models\InventoryLocation::orderBy('id')->first();
            $originalQuantity = 0;
            if ($location) {
                \App\Models\Inventory\StockLevel::updateOrCreate(
                    [
                        'item_id' => $item->id,
                        'inventory_location_id' => $location->id,
                    ],
                    [
                        'quantity' => $originalQuantity,
                    ]
                );
            }
        }
    }
}
