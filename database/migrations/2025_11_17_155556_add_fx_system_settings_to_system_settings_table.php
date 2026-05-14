<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\SystemSetting;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add FX Realized Gain Account setting
        SystemSetting::updateOrCreate(
            ['key' => 'fx_realized_gain_account_id'],
            [
                'value' => null,
                'type' => 'integer',
                'group' => 'fx',
                'label' => 'FX Realized Gain Account',
                'description' => 'Chart of account for recording realized foreign exchange gains',
                'is_public' => false
            ]
        );

        // Add FX Realized Loss Account setting
        SystemSetting::updateOrCreate(
            ['key' => 'fx_realized_loss_account_id'],
            [
                'value' => null,
                'type' => 'integer',
                'group' => 'fx',
                'label' => 'FX Realized Loss Account',
                'description' => 'Chart of account for recording realized foreign exchange losses',
                'is_public' => false
            ]
        );

        // Add FX Unrealized Gain Account setting
        SystemSetting::updateOrCreate(
            ['key' => 'fx_unrealized_gain_account_id'],
            [
                'value' => null,
                'type' => 'integer',
                'group' => 'fx',
                'label' => 'FX Unrealized Gain Account',
                'description' => 'Chart of account for recording unrealized foreign exchange gains',
                'is_public' => false
            ]
        );

        // Add FX Unrealized Loss Account setting
        SystemSetting::updateOrCreate(
            ['key' => 'fx_unrealized_loss_account_id'],
            [
                'value' => null,
                'type' => 'integer',
                'group' => 'fx',
                'label' => 'FX Unrealized Loss Account',
                'description' => 'Chart of account for recording unrealized foreign exchange losses',
                'is_public' => false
            ]
        );

        // Add Functional Currency setting
        SystemSetting::updateOrCreate(
            ['key' => 'functional_currency'],
            [
                'value' => 'TZS',
                'type' => 'string',
                'group' => 'fx',
                'label' => 'Functional Currency',
                'description' => 'Company\'s functional currency (base currency for reporting)',
                'is_public' => false
            ]
        );

        // Add FX Rate Override Threshold setting
        SystemSetting::updateOrCreate(
            ['key' => 'fx_rate_override_threshold'],
            [
                'value' => '5',
                'type' => 'decimal',
                'group' => 'fx',
                'label' => 'FX Rate Override Threshold (%)',
                'description' => 'Percentage threshold for rate override approval. If rate override exceeds this percentage, approval is required.',
                'is_public' => false
            ]
        );

        // Add FX Revaluation Approval Required setting
        SystemSetting::updateOrCreate(
            ['key' => 'fx_revaluation_approval_required'],
            [
                'value' => '0',
                'type' => 'boolean',
                'group' => 'fx',
                'label' => 'FX Revaluation Approval Required',
                'description' => 'Require approval before posting FX revaluation journal entries',
                'is_public' => false
            ]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        SystemSetting::where('key', 'fx_realized_gain_account_id')->delete();
        SystemSetting::where('key', 'fx_realized_loss_account_id')->delete();
        SystemSetting::where('key', 'fx_unrealized_gain_account_id')->delete();
        SystemSetting::where('key', 'fx_unrealized_loss_account_id')->delete();
        SystemSetting::where('key', 'functional_currency')->delete();
        SystemSetting::where('key', 'fx_rate_override_threshold')->delete();
        SystemSetting::where('key', 'fx_revaluation_approval_required')->delete();
    }
};
