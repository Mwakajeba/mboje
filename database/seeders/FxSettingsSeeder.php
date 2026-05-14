<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SystemSetting;
use App\Models\ChartAccount;

class FxSettingsSeeder extends Seeder
{
    /**
     * Seed FX settings based on the standard chart of accounts.
     *
     * This will automatically map the FX-related system settings to the
     * corresponding chart of accounts using the default account codes from
     * the main ChartAccountSeeder:
     *  - 4103: Foreign Exchange Gain - Realized
     *  - 5303: Foreign Exchange Loss - Realized
     *  - 4125: Foreign Exchange Gain - Unrealized
     *  - 5600: Foreign Exchange Loss - Unrealized
     */
    public function run(): void
    {
        $mappings = [
            'fx_realized_gain_account_id' => [
                'code' => '4103',
                'name' => 'Foreign Exchange Gain - Realized',
                'label' => 'FX Realized Gain Account',
                'description' => 'Chart of account for recording realized foreign exchange gains',
            ],
            'fx_realized_loss_account_id' => [
                'code' => '5303',
                'name' => 'Foreign Exchange Loss - Realized',
                'label' => 'FX Realized Loss Account',
                'description' => 'Chart of account for recording realized foreign exchange losses',
            ],
            'fx_unrealized_gain_account_id' => [
                'code' => '4125',
                'name' => 'Foreign Exchange Gain - Unrealized',
                'label' => 'FX Unrealized Gain Account',
                'description' => 'Chart of account for recording unrealized foreign exchange gains',
            ],
            'fx_unrealized_loss_account_id' => [
                'code' => '5600',
                'name' => 'Foreign Exchange Loss - Unrealized',
                'label' => 'FX Unrealized Loss Account',
                'description' => 'Chart of account for recording unrealized foreign exchange losses',
            ],
        ];

        foreach ($mappings as $settingKey => $config) {
            $account = ChartAccount::where('account_code', $config['code'])->first();

            if (! $account) {
                if ($this->command) {
                    $this->command->warn(
                        sprintf(
                            'FX Settings Seeder: chart account not found for code %s (%s)',
                            $config['code'],
                            $config['name']
                        )
                    );
                }
                continue;
            }

            SystemSetting::setValue(
                $settingKey,
                $account->id,
                'integer',
                'fx',
                $config['label'],
                $config['description']
            );

            if ($this->command) {
                $this->command->info(
                    sprintf(
                        'FX Settings Seeder: set %s to account %s (%s)',
                        $settingKey,
                        $config['code'],
                        $account->id
                    )
                );
            }
        }

        SystemSetting::clearCache();
    }
}

