<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CashFlowLineItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get cash flow categories
        $operatingId = DB::table('cash_flow_categories')->where('name', 'Operating Activities')->value('id');
        $investingId = DB::table('cash_flow_categories')->where('name', 'Investing Activities')->value('id');
        $financingId = DB::table('cash_flow_categories')->where('name', 'Financing Activities')->value('id');
        
        $lineItems = [
            // ===== OPERATING ACTIVITIES =====
            [
                'cash_flow_category_id' => $operatingId,
                'name' => 'Cash receipts from customers',
                'description' => 'Cash received from customers for goods and services',
                'sort_order' => 10,
                'is_subtotal' => false,
                'account_code_prefix' => null,
                'transaction_types' => json_encode(['receipt', 'cash_sale', 'pos_sale', 'customer_payment']),
                'is_active' => true,
            ],
            [
                'cash_flow_category_id' => $operatingId,
                'name' => 'Cash paid to suppliers',
                'description' => 'Cash paid to suppliers for goods and services',
                'sort_order' => 20,
                'is_subtotal' => false,
                'account_code_prefix' => null,
                'transaction_types' => json_encode(['payment', 'cash_purchase', 'supplier_payment']),
                'is_active' => true,
            ],
            [
                'cash_flow_category_id' => $operatingId,
                'name' => 'Cash paid to employees',
                'description' => 'Cash paid for salaries, wages, and employee benefits',
                'sort_order' => 30,
                'is_subtotal' => false,
                'account_code_prefix' => '5010',
                'transaction_types' => json_encode(['payroll_payment', 'salary_payment']),
                'is_active' => true,
            ],
            [
                'cash_flow_category_id' => $operatingId,
                'name' => 'Cash generated from operations',
                'description' => 'Subtotal of cash generated before interest and tax',
                'sort_order' => 40,
                'is_subtotal' => true,
                'account_code_prefix' => null,
                'transaction_types' => null,
                'is_active' => true,
            ],
            [
                'cash_flow_category_id' => $operatingId,
                'name' => 'Interest paid',
                'description' => 'Interest paid on borrowings and finance leases',
                'sort_order' => 50,
                'is_subtotal' => false,
                'account_code_prefix' => '6010',
                'transaction_types' => json_encode(['interest_payment']),
                'is_active' => true,
            ],
            [
                'cash_flow_category_id' => $operatingId,
                'name' => 'Income tax paid',
                'description' => 'Income tax paid to tax authorities',
                'sort_order' => 60,
                'is_subtotal' => false,
                'account_code_prefix' => '6500',
                'transaction_types' => json_encode(['tax_payment']),
                'is_active' => true,
            ],
            [
                'cash_flow_category_id' => $operatingId,
                'name' => 'Net cash from operating activities',
                'description' => 'Total net cash generated from operating activities',
                'sort_order' => 70,
                'is_subtotal' => false,
                'is_total' => true,
                'account_code_prefix' => null,
                'transaction_types' => null,
                'is_active' => true,
            ],
            
            // ===== INVESTING ACTIVITIES =====
            [
                'cash_flow_category_id' => $investingId,
                'name' => 'Purchase of property, plant and equipment',
                'description' => 'Cash paid for acquisition of property, plant and equipment',
                'sort_order' => 10,
                'is_subtotal' => false,
                'account_code_prefix' => '1500',
                'transaction_types' => json_encode(['asset_purchase', 'fixed_asset_acquisition']),
                'is_active' => true,
            ],
            [
                'cash_flow_category_id' => $investingId,
                'name' => 'Proceeds from sale of property, plant and equipment',
                'description' => 'Cash received from disposal of property, plant and equipment',
                'sort_order' => 20,
                'is_subtotal' => false,
                'account_code_prefix' => '1500',
                'transaction_types' => json_encode(['asset_disposal', 'fixed_asset_sale']),
                'is_active' => true,
            ],
            [
                'cash_flow_category_id' => $investingId,
                'name' => 'Purchase of intangible assets',
                'description' => 'Cash paid for acquisition of intangible assets',
                'sort_order' => 30,
                'is_subtotal' => false,
                'account_code_prefix' => '1600',
                'transaction_types' => json_encode(['intangible_asset_purchase']),
                'is_active' => true,
            ],
            [
                'cash_flow_category_id' => $investingId,
                'name' => 'Purchase of investments',
                'description' => 'Cash paid for acquisition of investments (shares, bonds, etc.)',
                'sort_order' => 40,
                'is_subtotal' => false,
                'account_code_prefix' => '1700',
                'transaction_types' => json_encode(['investment_purchase']),
                'is_active' => true,
            ],
            [
                'cash_flow_category_id' => $investingId,
                'name' => 'Proceeds from sale of investments',
                'description' => 'Cash received from disposal of investments',
                'sort_order' => 50,
                'is_subtotal' => false,
                'account_code_prefix' => '1700',
                'transaction_types' => json_encode(['investment_sale']),
                'is_active' => true,
            ],
            [
                'cash_flow_category_id' => $investingId,
                'name' => 'Interest received',
                'description' => 'Interest received on investments and bank deposits',
                'sort_order' => 60,
                'is_subtotal' => false,
                'account_code_prefix' => '4010',
                'transaction_types' => json_encode(['interest_receipt']),
                'is_active' => true,
            ],
            [
                'cash_flow_category_id' => $investingId,
                'name' => 'Dividends received',
                'description' => 'Dividends received from investments in subsidiaries, associates, and other investments',
                'sort_order' => 70,
                'is_subtotal' => false,
                'account_code_prefix' => '4020',
                'transaction_types' => json_encode(['dividend_receipt']),
                'is_active' => true,
            ],
            [
                'cash_flow_category_id' => $investingId,
                'name' => 'Loans advanced to related parties',
                'description' => 'Cash advanced as loans to related parties',
                'sort_order' => 80,
                'is_subtotal' => false,
                'account_code_prefix' => '1250',
                'transaction_types' => json_encode(['loan_advanced']),
                'is_active' => true,
            ],
            [
                'cash_flow_category_id' => $investingId,
                'name' => 'Repayment of loans from related parties',
                'description' => 'Cash received from repayment of loans to related parties',
                'sort_order' => 90,
                'is_subtotal' => false,
                'account_code_prefix' => '1250',
                'transaction_types' => json_encode(['loan_repayment_received']),
                'is_active' => true,
            ],
            [
                'cash_flow_category_id' => $investingId,
                'name' => 'Net cash used in investing activities',
                'description' => 'Total net cash used in investing activities',
                'sort_order' => 100,
                'is_subtotal' => false,
                'is_total' => true,
                'account_code_prefix' => null,
                'transaction_types' => null,
                'is_active' => true,
            ],
            
            // ===== FINANCING ACTIVITIES =====
            [
                'cash_flow_category_id' => $financingId,
                'name' => 'Proceeds from issuance of share capital',
                'description' => 'Cash received from issuance of ordinary or preference shares',
                'sort_order' => 10,
                'is_subtotal' => false,
                'account_code_prefix' => '3010',
                'transaction_types' => json_encode(['share_issuance', 'capital_contribution']),
                'is_active' => true,
            ],
            [
                'cash_flow_category_id' => $financingId,
                'name' => 'Proceeds from long-term borrowings',
                'description' => 'Cash received from bank loans, bonds, and other long-term borrowings',
                'sort_order' => 20,
                'is_subtotal' => false,
                'account_code_prefix' => '2500',
                'transaction_types' => json_encode(['loan_receipt', 'borrowing_proceeds']),
                'is_active' => true,
            ],
            [
                'cash_flow_category_id' => $financingId,
                'name' => 'Proceeds from short-term borrowings',
                'description' => 'Cash received from short-term loans and overdrafts',
                'sort_order' => 30,
                'is_subtotal' => false,
                'account_code_prefix' => '2110',
                'transaction_types' => json_encode(['short_term_loan_receipt']),
                'is_active' => true,
            ],
            [
                'cash_flow_category_id' => $financingId,
                'name' => 'Repayment of borrowings',
                'description' => 'Cash paid for repayment of bank loans, bonds, and other borrowings',
                'sort_order' => 40,
                'is_subtotal' => false,
                'account_code_prefix' => '2500',
                'transaction_types' => json_encode(['loan_repayment', 'borrowing_repayment']),
                'is_active' => true,
            ],
            [
                'cash_flow_category_id' => $financingId,
                'name' => 'Payment of lease liabilities',
                'description' => 'Cash paid for principal portion of lease payments (IFRS 16)',
                'sort_order' => 50,
                'is_subtotal' => false,
                'account_code_prefix' => '2520',
                'transaction_types' => json_encode(['lease_payment']),
                'is_active' => true,
            ],
            [
                'cash_flow_category_id' => $financingId,
                'name' => 'Dividends paid to shareholders',
                'description' => 'Cash paid as dividends to equity holders',
                'sort_order' => 60,
                'is_subtotal' => false,
                'account_code_prefix' => '3040',
                'transaction_types' => json_encode(['dividend_payment']),
                'is_active' => true,
            ],
            [
                'cash_flow_category_id' => $financingId,
                'name' => 'Purchase of treasury shares',
                'description' => 'Cash paid for purchase of own shares',
                'sort_order' => 70,
                'is_subtotal' => false,
                'account_code_prefix' => '3060',
                'transaction_types' => json_encode(['treasury_share_purchase']),
                'is_active' => true,
            ],
            [
                'cash_flow_category_id' => $financingId,
                'name' => 'Proceeds from sale of treasury shares',
                'description' => 'Cash received from resale of treasury shares',
                'sort_order' => 80,
                'is_subtotal' => false,
                'account_code_prefix' => '3060',
                'transaction_types' => json_encode(['treasury_share_sale']),
                'is_active' => true,
            ],
            [
                'cash_flow_category_id' => $financingId,
                'name' => 'Net cash from financing activities',
                'description' => 'Total net cash from financing activities',
                'sort_order' => 90,
                'is_subtotal' => false,
                'is_total' => true,
                'account_code_prefix' => null,
                'transaction_types' => null,
                'is_active' => true,
            ],
        ];
        
        foreach ($lineItems as $item) {
            DB::table('cash_flow_line_items')->insertOrIgnore(array_merge($item, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
        
        $this->command->info('Cash flow line items seeded successfully!');
    }
}
