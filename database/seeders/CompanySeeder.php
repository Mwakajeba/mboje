<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Company;

class CompanySeeder extends Seeder
{
    public function run()
    {
        Company::create([
            'name' => 'SAFCO FINTECH LTD',
            'email' => 'info@safco.com',
            'phone' => '255754000000',
            'address' => 'Dar es Salaam, Tanzania',
            'logo' => 'safco_logo.png',
            'license_number' => 'LIC001',
            'registration_date' => '2020-01-01',
            'status' => 'active',
        ]);
    }
}
