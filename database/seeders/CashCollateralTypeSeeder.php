<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CashCollateralType;

class CashCollateralTypeSeeder extends Seeder
{
    /**
     * Seed cash collateral types. Customer Deposit type uses chart_account_id 28
     * (Customer Deposits - account code 2001).
     */
    public function run(): void
    {
        CashCollateralType::updateOrCreate(
            ['name' => 'Customer Deposit'],
            [
                'chart_account_id' => 28,
                'description' => 'Customer deposit account linked to Customer Deposits (2001)',
                'is_active' => true,
            ]
        );
    }
}
