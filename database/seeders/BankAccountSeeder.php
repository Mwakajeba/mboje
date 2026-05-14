<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BankAccount;
use App\Models\ChartAccount;

class BankAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the cash account from chart accounts
        $cashAccount = ChartAccount::where('account_name', 'Cash on Hand')->first();
        $bankAccountMain = ChartAccount::where('account_name', 'CRDB Bank Account')->first();
        $bankAccountSavings = ChartAccount::where('account_name', 'NMB Bank Account')->first();
        $bankAccountFixed = ChartAccount::where('account_name', 'NBC Bank Account')->first();

        if (!$cashAccount) {
            $this->command->error('Cash on Hand account not found. Please run ChartAccountSeeder first.');
            return;
        }

        if (!$bankAccountMain) {
            $this->command->error('CRDB Bank Account not found. Please run ChartAccountSeeder first.');
            return;
        }

        $bankAccounts = [
            [
                'chart_account_id' => $cashAccount->id,
                'name' => 'Cash Register',
                'account_number' => 'CASH-001',
            ],
            [
                'chart_account_id' => $bankAccountMain->id,
                'name' => 'CRDB Bank',
                'account_number' => '1234567890',
            ],
            [
                'chart_account_id' => $bankAccountSavings ? $bankAccountSavings->id : $bankAccountMain->id,
                'name' => 'NMB Bank',
                'account_number' => '0987654321',
            ],
            [
                'chart_account_id' => $bankAccountFixed ? $bankAccountFixed->id : $bankAccountMain->id,
                'name' => 'NBC Bank',
                'account_number' => '1122334455',
            ],
        ];

        foreach ($bankAccounts as $accountData) {
            BankAccount::firstOrCreate(
                ['account_number' => $accountData['account_number']],
                $accountData
            );
        }

        $this->command->info('Bank accounts seeded successfully!');
    }
}
