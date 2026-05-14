<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\SystemSetting;
use App\Models\ChartAccount;

class InventorySettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Use fixed seeded IDs for deterministic defaults aligned with the new ChartAccountSeeder
        $inventoryAccountId = DB::table('chart_accounts')->where('id', 185)->value('id'); // Merchandise Inventory
        $salesAccountId = DB::table('chart_accounts')->where('id', 53)->value('id'); // Sales – Goods
        $costAccountId = DB::table('chart_accounts')->where('id', 173)->value('id'); // Cost of Goods Sold

        // Opening Balance Account - Retained Earnings (Equity)
        $openingBalanceAccountId = DB::table('chart_accounts')->where('id', 41)->value('id'); // Retained Earnings

        // VAT Account - VAT Control Account (Liability)
        $vatAccountId = DB::table('chart_accounts')->where('id', 36)->value('id'); // VAT Control Account

        // Withholding Tax Payable (Liability)
        $withholdingTaxAccountId = DB::table('chart_accounts')->where('id', 37)->value('id'); // Withholding Tax Payable

        // Withholding Tax Expense - no dedicated account in new CoA, use Discount Expense (172) as closest fit
        $withholdingTaxExpenseAccountId = DB::table('chart_accounts')->where('id', 172)->value('id'); // Discount Expense

        // Purchase Payable Account - Trade Payables (Liability)
        $purchasePayableAccountId = DB::table('chart_accounts')->where('id', 30)->value('id'); // Trade Payables

        // Discount Expense (Expense)
        $discountAccountId = DB::table('chart_accounts')->where('id', 172)->value('id'); // Discount Expense

        // Discount Income Account - Discount Income (Revenue)
        $discountIncomeAccountId = DB::table('chart_accounts')->where('id', 52)->value('id'); // Discount Income

        // Early Payment Discount (Expense) - use Discount Expense
        $earlyPaymentDiscountAccountId = DB::table('chart_accounts')->where('id', 172)->value('id');

        // Late Payment Fees / Penalty Income (Revenue)
        $latePaymentFeesAccountId = DB::table('chart_accounts')->where('id', 199)->value('id'); // Late Fee Income

        // Accounts Receivable (Asset) - Trade Receivables
        $receivableAccountId = DB::table('chart_accounts')->where('id', 6)->value('id');

        // Cash on Hand Account - Cash on Hand (Asset)
        $cashAccountId = DB::table('chart_accounts')->where('account_code', '1001')->value('id'); // Cash on Hand (1001)

        // Transport Revenue Account - Transport Revenue (Revenue)
        $transportRevenueAccountId = DB::table('chart_accounts')->where('id', 212)->value('id');

        // Cheque Issued Account - Cheque Issued - Outstanding (Liability)
        $chequeIssuedAccountId = DB::table('chart_accounts')->where('id', 749)->value('id'); // Cheque Issued - Outstanding

        // Cheques in Transit Account - Cheques in Transit (Asset)
        $chequesInTransitAccountId = DB::table('chart_accounts')->where('id', 750)->value('id'); // Cheques in Transit

        // Inventory Loss Expense Account - Inventory Loss Expense (Expense)
        $inventoryLossExpenseAccountId = DB::table('chart_accounts')->where('id', 209)->value('id'); // Inventory Loss Expense

        // Inventory Gain Income Account - Inventory Gain Income (Revenue)
        $inventoryGainIncomeAccountId = DB::table('chart_accounts')->where('id', 210)->value('id'); // Inventory Gain Income

        // Define inventory settings with proper default account mappings
        $inventorySettings = [
            'inventory_low_stock_threshold' => [
                'value' => 10,
                'type' => 'integer',
                'group' => 'inventory',
                'label' => 'Low Stock Threshold',
                'description' => 'Threshold for low stock alerts'
            ],
            'inventory_auto_reorder_point' => [
                'value' => 5,
                'type' => 'integer',
                'group' => 'inventory',
                'label' => 'Auto Reorder Point',
                'description' => 'Automatic reorder point for inventory'
            ],
            'inventory_default_unit' => [
                'value' => 'pieces',
                'type' => 'string',
                'group' => 'inventory',
                'label' => 'Default Unit',
                'description' => 'Default unit of measure for inventory items'
            ],
            'inventory_cost_method' => [
                'value' => 'fifo',
                'type' => 'string',
                'group' => 'inventory',
                'label' => 'Cost Method',
                'description' => 'Inventory valuation method (FIFO or Weighted Average)'
            ],
            'inventory_barcode_prefix' => [
                'value' => 'INV',
                'type' => 'string',
                'group' => 'inventory',
                'label' => 'Barcode Prefix',
                'description' => 'Prefix for auto-generated barcodes'
            ],
            'inventory_enable_batch_tracking' => [
                'value' => true,
                'type' => 'boolean',
                'group' => 'inventory',
                'label' => 'Enable Batch Tracking',
                'description' => 'Enable batch/lot tracking for inventory'
            ],
            'inventory_enable_expiry_tracking' => [
                'value' => true,
                'type' => 'boolean',
                'group' => 'inventory',
                'label' => 'Enable Expiry Tracking',
                'description' => 'Enable expiry date tracking for inventory'
            ],
            'inventory_global_expiry_warning_days' => [
                'value' => 30,
                'type' => 'integer',
                'group' => 'inventory',
                'label' => 'Global Expiry Warning Days',
                'description' => 'Default number of days before expiry to show warnings (applies to all items)'
            ],
            'inventory_enable_serial_tracking' => [
                'value' => false,
                'type' => 'boolean',
                'group' => 'inventory',
                'label' => 'Enable Serial Tracking',
                'description' => 'Enable serial number tracking for inventory'
            ],
            'inventory_default_location' => [
                'value' => 1,
                'type' => 'integer',
                'group' => 'inventory',
                'label' => 'Default Location',
                'description' => 'Default storage location for inventory items (inventory_locations.id)'
            ],
            // Default Inventory Account - Asset account for inventory valuation
            'inventory_default_inventory_account' => [
                'value' => $inventoryAccountId ?: null,
                'type' => 'integer',
                'group' => 'inventory',
                'label' => 'Default Inventory Account',
                'description' => 'Default chart account for inventory asset valuation'
            ],
            // Default Sales Account - Revenue account for sales transactions
            'inventory_default_sales_account' => [
                'value' => $salesAccountId ?: null,
                'type' => 'integer',
                'group' => 'inventory',
                'label' => 'Default Sales Account',
                'description' => 'Default chart account for sales revenue'
            ],
            // Default Cost Account - Expense account for cost of goods sold
            'inventory_default_cost_account' => [
                'value' => $costAccountId ?: null,
                'type' => 'integer',
                'group' => 'inventory',
                'label' => 'Default Cost Account',
                'description' => 'Default chart account for cost of goods sold'
            ],
            // Default Opening Balance Account - Equity account for opening stock
            'inventory_default_opening_balance_account' => [
                'value' => $openingBalanceAccountId ?: null,
                'type' => 'integer',
                'group' => 'inventory',
                'label' => 'Default Opening Balance Account',
                'description' => 'Default chart account for opening stock (Retained Earnings)'
            ],
            // Default VAT Account - Liability account for VAT tracking
            'inventory_default_vat_account' => [
                'value' => $vatAccountId ?: null,
                'type' => 'integer',
                'group' => 'inventory',
                'label' => 'Default VAT Account',
                'description' => 'Default chart account for VAT liability tracking'
            ],
            // Default Withholding Tax Account - Liability account for withholding tax
            'inventory_default_withholding_tax_account' => [
                'value' => $withholdingTaxAccountId ?: null,
                'type' => 'integer',
                'group' => 'inventory',
                'label' => 'Default Withholding Tax Account',
                'description' => 'Default chart account for withholding tax liability tracking'
            ],
            // Default Withholding Tax Expense Account - Expense account for withholding tax
            'inventory_default_withholding_tax_expense_account' => [
                'value' => $withholdingTaxExpenseAccountId ?: null,
                'type' => 'integer',
                'group' => 'inventory',
                'label' => 'Default Withholding Tax Expense Account',
                'description' => 'Default chart account for withholding tax expense tracking'
            ],
            // Default Purchase Payable Account - Liability account for purchase payables
            'inventory_default_purchase_payable_account' => [
                'value' => $purchasePayableAccountId ?: null,
                'type' => 'integer',
                'group' => 'inventory',
                'label' => 'Default Purchase Payable Account',
                'description' => 'Default chart account for purchase payable tracking'
            ],
            // Default Discount Account - Expense account for discounts given
            'inventory_default_discount_account' => [
                'value' => $discountAccountId ?: null,
                'type' => 'integer',
                'group' => 'inventory',
                'label' => 'Default Discount Account',
                'description' => 'Default chart account for discount expense tracking'
            ],
            // Default Discount Income Account - Revenue account for discounts received
            'inventory_default_discount_income_account' => [
                'value' => $discountIncomeAccountId ?: null,
                'type' => 'integer',
                'group' => 'inventory',
                'label' => 'Default Discount Income Account',
                'description' => 'Default chart account for discount income tracking'
            ],
            // Default Early Payment Discount Account - Expense
            'inventory_default_early_payment_discount_account' => [
                'value' => $earlyPaymentDiscountAccountId ?: null,
                'type' => 'integer',
                'group' => 'inventory',
                'label' => 'Default Early Payment Discount Account',
                'description' => 'Default chart account for early payment discount expense'
            ],
            // Default Late Payment Fees Account - Revenue
            'inventory_default_late_payment_fees_account' => [
                'value' => $latePaymentFeesAccountId ?: null,
                'type' => 'integer',
                'group' => 'inventory',
                'label' => 'Default Late Payment Fees Account',
                'description' => 'Default chart account for late payment fees income'
            ],
            // Default Accounts Receivable Account - Asset
            'inventory_default_receivable_account' => [
                'value' => $receivableAccountId ?: null,
                'type' => 'integer',
                'group' => 'inventory',
                'label' => 'Default Accounts Receivable Account',
                'description' => 'Default chart account for trade receivables'
            ],
            // Default Cash Account - Asset account for cash transactions
            'inventory_default_cash_account' => [
                'value' => $cashAccountId ?: null,
                'type' => 'integer',
                'group' => 'inventory',
                'label' => 'Default Cash Account',
                'description' => 'Default chart account for cash on hand (used in cash sales, cash purchases, receipts, payments)'
            ],
            // Default Transport Revenue Account - Revenue
            'inventory_default_transport_revenue_account' => [
                'value' => $transportRevenueAccountId ?: null,
                'type' => 'integer',
                'group' => 'inventory',
                'label' => 'Default Transport Revenue Account',
                'description' => 'Default chart account for transport/delivery service revenue'
            ],
            // Cheque Issued Account - Liability account for tracking outstanding cheques
            'cheque_issued_account_id' => [
                'value' => $chequeIssuedAccountId ?: null,
                'type' => 'integer',
                'group' => 'inventory',
                'label' => 'Cheque Issued Account',
                'description' => 'Contra account for tracking outstanding cheques issued but not yet cleared'
            ],
            // Cheques in Transit Account - Asset account for tracking cheques received but not yet deposited
            'cheques_in_transit_account_id' => [
                'value' => $chequesInTransitAccountId ?: null,
                'type' => 'integer',
                'group' => 'inventory',
                'label' => 'Cheques in Transit Account',
                'description' => 'Asset account for tracking cheques received but not yet deposited to bank'
            ],
            // Inventory Loss Expense Account - Expense account for inventory shortages/losses
            'inventory_loss_expense_account' => [
                'value' => $inventoryLossExpenseAccountId ?: null,
                'type' => 'integer',
                'group' => 'inventory',
                'label' => 'Inventory Loss Expense Account',
                'description' => 'Chart account for recording inventory shortages/losses from count adjustments'
            ],
            // Inventory Gain Income Account - Revenue account for inventory surpluses/gains
            'inventory_gain_income_account' => [
                'value' => $inventoryGainIncomeAccountId ?: null,
                'type' => 'integer',
                'group' => 'inventory',
                'label' => 'Inventory Gain Income Account',
                'description' => 'Chart account for recording inventory surpluses/gains from count adjustments'
            ],
            'inventory_default_is_withholding_receivable' => [
                'value' => false,
                'type' => 'boolean',
                'group' => 'inventory',
                'label' => 'Default Withholding Tax Type',
                'description' => 'Default withholding tax type (receivable/payable)'
            ],
            'inventory_expiry_warning_days' => [
                'value' => 30,
                'type' => 'integer',
                'group' => 'inventory',
                'label' => 'Expiry Warning Days',
                'description' => 'Number of days before expiry to show warnings (default: 30 days)'
            ],
            // Variance Value Threshold - Threshold for flagging high-value variances
            'inventory_variance_value_threshold' => [
                'value' => 50000,
                'type' => 'decimal',
                'group' => 'inventory',
                'label' => 'Variance Value Threshold',
                'description' => 'Variance value threshold in TZS. Variances exceeding this value will be flagged as high-value (default: 50,000 TZS)'
            ],
            // Variance Percentage Threshold - Threshold for flagging high-percentage variances
            'inventory_variance_percentage_threshold' => [
                'value' => 5,
                'type' => 'decimal',
                'group' => 'inventory',
                'label' => 'Variance Percentage Threshold',
                'description' => 'Variance percentage threshold. Variances exceeding this percentage will be flagged as high-value (default: 5%)'
            ],
            'inventory_default_invoice_due_days' => [
                'value' => 30,
                'type' => 'integer',
                'group' => 'inventory',
                'label' => 'Default Invoice Due Days',
                'description' => 'Number of days from invoice date to due date for sales invoices created from inventory (0 = due on invoice date)'
            ],
        ];

        // Save or update each setting
        foreach ($inventorySettings as $key => $settingData) {
            SystemSetting::updateOrCreate(
                ['key' => $key],
                $settingData
            );
        }

        $this->command->info('Inventory settings seeded successfully!');
    }
} 