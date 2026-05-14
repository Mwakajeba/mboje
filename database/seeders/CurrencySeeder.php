<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Currency;
use App\Models\Company;

class CurrencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all companies
        $companies = Company::all();

        if ($companies->isEmpty()) {
            $this->command->warn('No companies found. Please seed companies first.');
            return;
        }

        // Common currencies used in East Africa and globally
        $currencies = [
            ['code' => 'TZS', 'name' => 'Tanzanian Shilling', 'decimal_places' => 2],
            ['code' => 'USD', 'name' => 'US Dollar', 'decimal_places' => 2],
            ['code' => 'EUR', 'name' => 'Euro', 'decimal_places' => 2],
            ['code' => 'GBP', 'name' => 'British Pound', 'decimal_places' => 2],
            ['code' => 'KES', 'name' => 'Kenyan Shilling', 'decimal_places' => 2],
            ['code' => 'UGX', 'name' => 'Ugandan Shilling', 'decimal_places' => 0],
            ['code' => 'RWF', 'name' => 'Rwandan Franc', 'decimal_places' => 0],
            ['code' => 'BIF', 'name' => 'Burundian Franc', 'decimal_places' => 0],
            ['code' => 'CDF', 'name' => 'Congolese Franc', 'decimal_places' => 2],
            ['code' => 'ZAR', 'name' => 'South African Rand', 'decimal_places' => 2],
            ['code' => 'CNY', 'name' => 'Chinese Yuan', 'decimal_places' => 2],
            ['code' => 'JPY', 'name' => 'Japanese Yen', 'decimal_places' => 0],
            ['code' => 'INR', 'name' => 'Indian Rupee', 'decimal_places' => 2],
            ['code' => 'AED', 'name' => 'UAE Dirham', 'decimal_places' => 2],
            ['code' => 'SAR', 'name' => 'Saudi Riyal', 'decimal_places' => 2],
            ['code' => 'CHF', 'name' => 'Swiss Franc', 'decimal_places' => 2],
            ['code' => 'AUD', 'name' => 'Australian Dollar', 'decimal_places' => 2],
            ['code' => 'CAD', 'name' => 'Canadian Dollar', 'decimal_places' => 2],
        ];

        foreach ($companies as $company) {
            $this->command->info("Seeding currencies for company: {$company->name}");

            foreach ($currencies as $currencyData) {
                Currency::firstOrCreate(
                    [
                        'currency_code' => $currencyData['code'],
                        'company_id' => $company->id,
                    ],
                    [
                        'currency_name' => $currencyData['name'],
                        'decimal_places' => $currencyData['decimal_places'],
                        'is_active' => true,
                    ]
                );
            }

            $this->command->info("Created " . count($currencies) . " currencies for {$company->name}");
        }

        $this->command->info('Currency seeding completed!');
    }
}
