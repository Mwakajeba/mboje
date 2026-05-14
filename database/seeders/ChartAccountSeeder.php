<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ChartAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * This seeder recreates the chart of accounts you provided from
     * your SQL dump. It uses updateOrInsert so it is safe to re-run and
     * will keep IDs stable for all referenced accounts.
     */
    public function run(): void
    {
        $now = now();

        // WARNING:
        // This seeder TRUNCATES the `chart_accounts` table before inserting
        // the full chart of accounts. Only use this in environments where it
        // is safe to drop and recreate all chart accounts.
        Schema::disableForeignKeyConstraints();
        DB::table('chart_accounts')->truncate();
        Schema::enableForeignKeyConstraints();

        // Map logical group IDs (from your original dump) to actual
        // account_class_groups IDs in this database.
        $logicalGroupNames = [
            1  => 'Trade & Other Receivables',
            2  => 'Property, Plant & Equipment (PPE)',
            5  => 'Trade & Other Payables',
            6  => 'Borrowings',
            7  => 'Share Capital',
            8  => 'Reserves',
            9  => 'Dividends',
            10 => 'Sales Revenue',
            11 => 'Other Income',
            12 => 'Accumulated Depreciation, Amortization & Impairment',
            13 => 'Right of Use Assets (IFRS 16)',
            14 => 'Intangible Assets (IAS 38)',
            16 => 'Cash & Cash Equivalents (IAS 7)',
            17 => 'Inventory (IAS 2)',
            18 => 'Prepayments & Other Current Assets',
            19 => 'Lease Liabilities (IFRS 16)',
            20 => 'Deferred Liabilities',
            21 => 'Short-term Borrowings',
            22 => 'Provisions (IAS 37)',
            23 => 'Other Current Liabilities',
            24 => 'Selling & Distribution',
            25 => 'Administrative Expenses',
            26 => 'Depreciation, Amortization & Impairment',
            27 => 'Finance Costs (IAS 23, IFRS 9)',
            28 => 'Taxation',
            29 => 'Cost of Sales',
            30 => 'Investment Properties',
            31 => 'Financial Assets Investment',
            32 => 'Employee Benefits',
            33 => 'Investment Income',
            34 => 'Non-Current Assets Held for Sale',
            35 => 'Liabilities Associated with Assets Held for Sale',
        ];

        $resolvedGroupIds = [];
        foreach ($logicalGroupNames as $logicalId => $name) {
            $id = DB::table('account_class_groups')
                ->where('name', $name)
                ->value('id');

            if ($id) {
                $resolvedGroupIds[$logicalId] = $id;
            }
        }

        $accounts = [
            // id, account_class_group_id, account_code, account_name, account_type,
            // parent_id, has_cash_flow, has_equity, cash_flow_category_id, equity_category_id

            // Cash & Cash Equivalents (IAS 7)
            [1, 16, '1001', 'Cash on Hand', 'parent', null, 1, 0, 4, null],
            [2, 16, '1002', 'Petty Cash', 'parent', null, 1, 0, 4, null],
            // Main operating bank account (CRDB)
            [3, 16, '1003', 'CRDB Bank Account', 'child', 642, 1, 0, 4, null],
            [4, 16, '1004', 'NMB Bank Account', 'child', 642, 1, 0, 4, null],
            [5, 16, '1005', 'NBC Bank Account', 'child', 642, 1, 0, 4, null],

            // Trade & Other Receivables
            [6, 1, '1101', 'Trade Receivables', 'parent', null, 1, 0, 1, null],
            [7, 1, '1102', 'Other Receivables', 'parent', null, 1, 0, 1, null],
            [8, 1, '1103', 'Staff Advances / Receivables', 'parent', null, 1, 0, 1, null],
            [9, 1, '1104', 'Corporate Tax Receivables', 'parent', null, 1, 0, 1, null],

            // Prepayments & Other Current Assets
            [12, 18, '1301', 'Prepaid Rent', 'parent', null, 1, 0, 1, null],
            [13, 18, '1302', 'Prepaid Insurance', 'parent', null, 1, 0, 1, null],

            // Other Current Liabilities
            [14, 23, '2303', 'Prepaid Rent Income', 'parent', null, 1, 0, 1, null],
            [28, 23, '2001', 'Customer Deposits', 'parent', null, 1, 0, 1, null],

            // Trade & Other Payables
            [30, 5, '2101', 'Trade Payables', 'parent', null, 1, 0, 1, null],
            [31, 23, '2102', 'Other Accrued Liabilities', 'parent', null, 1, 0, 1, null],
            [32, 5, '2103', 'Net Salary Payable', 'parent', null, 1, 0, 1, null],
            [34, 5, '2105', 'Accrued Expenses', 'parent', null, 1, 0, 1, null],
            [35, 5, '2106', 'Corporate Tax Payable', 'parent', null, 1, 0, 1, null],
            [36, 5, '2107', 'VAT Control Account', 'parent', null, 1, 0, 1, null],
            [37, 5, '2108', 'Withholding Tax Payable', 'parent', null, 1, 0, 1, null],
            [38, 5, '2109', 'Social Security Payable', 'parent', null, 1, 0, 1, null],

            // Reserves / Equity
            [41, 8, '3001', 'Retained Earnings', 'parent', null, 0, 1, null, 3],
            [42, 8, '3002', 'Current Year Earnings', 'parent', null, 0, 1, null, 4],
            [43, 7, '3101', 'Ordinary Share Capital', 'parent', null, 1, 1, null, 1],
            [45, 7, '3103', 'Preference Share Capital', 'parent', null, 1, 1, null, 1],

            // Other Income / Investment Income
            [46, 33, '4001', 'Interest Income', 'child', 48, 1, 0, 2, null],
            [47, 33, '4002', 'Bank Interest', 'child', 48, 1, 0, 2, null],
            [48, 11, '4003', 'Investment Income', 'parent', null, 1, 0, 2, null],
            [49, 11, '4101', 'Other Operating Income', 'parent', null, 1, 0, 1, null],
            [50, 11, '4102', 'Gain on Sale of Assets', 'parent', null, 1, 0, 1, null],
            [51, 11, '4103', 'Foreign Exchange Gain - Realized', 'parent', null, 1, 0, 1, null],
            [52, 11, '4104', 'Discount Income', 'parent', null, 1, 0, 1, null],
            [210, 11, '4106', 'Inventory Gain Income', 'parent', null, 1, 0, 1, null],

            // Operating Revenue
            [53, 10, '4201', 'Sales – Goods', 'parent', null, 1, 0, 1, null],
            [54, 10, '4202', 'Consultancy Income', 'child', 674, 1, 0, 1, null],
            [55, 11, '4203', 'Commission Income', 'parent', null, 1, 0, 1, null],
            [56, 11, '4204', 'Training Income', 'parent', null, 1, 0, 1, null],

            // Allowance for Doubtful Accounts
            [57, 1, '1109', 'Allowance for Doubtful Accounts', 'child', 6, 0, 0, null, null],

            // Employee Benefits (Expenses)
            [58, 32, '5101', 'Salaries and Wages', 'parent', null, 1, 0, 1, null],
            [59, 25, '5102', 'Rent Expense', 'parent', null, 1, 0, 1, null],
            [60, 25, '5103', 'Utilities Expense', 'parent', null, 1, 0, 1, null],
            [61, 25, '5104', 'Insurance Expense', 'parent', null, 1, 0, 1, null],
            [62, 25, '5105', 'Maintenance Expense', 'parent', null, 1, 0, 1, null],
            [63, 25, '5106', 'Office Supplies', 'parent', null, 1, 0, 1, null],
            [64, 25, '5107', 'Telephone Expense', 'parent', null, 1, 0, 1, null],
            [65, 25, '5108', 'Internet Expense', 'parent', null, 1, 0, 1, null],
            [66, 24, '5109', 'Transportation Expense', 'parent', null, 1, 0, 1, null],
            [67, 25, '5110', 'Meals and Entertainment', 'parent', null, 1, 0, 1, null],
            [68, 25, '5201', 'Professional Fees', 'parent', null, 1, 0, 1, null],
            [69, 25, '5202', 'Legal Fees', 'parent', null, 1, 0, 1, null],
            [70, 25, '5203', 'Accounting Fees', 'parent', null, 1, 0, 1, null],
            [71, 25, '5204', 'Audit Fees', 'parent', null, 1, 0, 1, null],

            // IAS 37 – Specific Provision Expenses (P&L)
            [820, 25, '5215', 'Warranty Expense', 'parent', null, 1, 0, 1, null],
            [821, 25, '5216', 'Restructuring Expense', 'parent', null, 1, 0, 1, null],
            [822, 25, '5217', 'Onerous Contract Expense', 'parent', null, 1, 0, 1, null],
            [823, 25, '5218', 'Employee Benefit Provision Expense', 'parent', null, 1, 0, 1, null],

            // Finance Costs
            [72, 27, '5205', 'Bank Charges', 'parent', null, 1, 0, 1, null],
            [800, 27, '5208', 'Loan Processing Fees', 'parent', null, 1, 0, 1, null],
            [824, 27, '5219', 'Finance Cost – Provision Unwinding', 'parent', null, 1, 0, 1, null],
            [73, 26, '5206', 'Depreciation – PPE', 'parent', null, 1, 0, 1, null],
            [74, 26, '5207', 'Amortization – Intangibles', 'parent', null, 1, 0, 1, null],

            // Penalty Income
            [100, 11, '4105', 'Penalty Income', 'parent', null, 1, 0, 1, null],

            // More Finance Costs / Expenses
            [166, 27, '5301', 'Interest Expense – Bank Loan', 'parent', null, 1, 0, 1, null],
            [168, 25, '5303', 'Foreign Exchange Loss - Realized', 'parent', null, 1, 0, 1, null],
            [169, 25, '5304', 'Loss on Sale of Assets', 'parent', null, 1, 0, 1, null],
            [170, 25, '5305', 'Bad Debt Expense', 'parent', null, 1, 0, 1, null],
            [171, 28, '5306', 'Income Tax Expense', 'parent', null, 1, 0, 1, null],
            [172, 29, '5307', 'Discount Expense', 'parent', null, 1, 0, 1, null],

            // Cost of Sales
            [173, 29, '5501', 'Cost of Goods Sold', 'parent', null, 1, 0, 1, null],
            [174, 29, '5502', 'Direct Labor', 'parent', null, 1, 0, 1, null],
            [175, 29, '5503', 'Direct Materials', 'parent', null, 1, 0, 1, null],
            [176, 29, '5504', 'Manufacturing Overhead', 'parent', null, 1, 0, 1, null],
            [178, 29, '5506', 'Freight and Carriage In', 'parent', null, 1, 0, 1, null],
            [179, 29, '5507', 'Import Duties and Taxes', 'parent', null, 1, 0, 1, null],
            [181, 29, '5509', 'Inventory Write-off', 'parent', null, 1, 0, 1, null],
            [182, 29, '5510', 'Inventory Obsolescence', 'parent', null, 1, 0, 1, null],
            [209, 29, '5511', 'Inventory Loss Expense', 'parent', null, 1, 0, 1, null],

            // Inventory
            [183, 1, '1160', 'Finished Goods', 'parent', null, 1, 0, 1, null],
            [184, 17, '1165', 'Raw Materials', 'parent', null, 1, 0, 1, null],
            [185, 17, '1170', 'Merchandise Inventory', 'parent', null, 1, 0, 1, null],

            // Property, Plant & Equipment (PPE)
            [186, 2, '1201', 'Land', 'parent', null, 1, 0, 2, null],
            [187, 2, '1208', 'Buildings', 'parent', null, 1, 0, 2, null],
            [188, 2, '1206', 'Furniture, Fixtures & Fittings', 'parent', null, 1, 0, 2, null],
            [189, 2, '1204', 'Hotel Equipment', 'parent', null, 1, 0, 2, null],

            // Accumulated Depreciation & Impairment
            [190, 12, '1180', 'Heritage or Cultural Assets - Accumulated Depreciation', 'parent', null, 0, 0, null, null],
            [191, 12, '1207', 'Furniture & Fixtures - Accumulated Depreciation', 'parent', null, 0, 0, null, null],
            [192, 12, '1205', 'Hotel Equipment - Accumulated Depreciation', 'parent', null, 0, 0, null, null],

            // Other Payables etc.
            [193, 5, '2110', 'Security Deposits Payable', 'parent', null, 1, 0, 1, null],
            
            // Cheque Issued Account (Contra account for outstanding cheques)
            [749, 23, '2112', 'Cheque Issued - Outstanding', 'parent', null, 1, 0, 1, null],
            
            // Cheques in Transit Account (Asset account for cheques received but not yet deposited)
            [750, 16, '1006', 'Cheques in Transit', 'parent', null, 1, 0, 1, null],

            // Hotel Revenue
            [194, 10, '4301', 'Hotel Room Revenue', 'parent', null, 1, 0, 1, null],
            [195, 10, '4302', 'Hotel Service Revenue', 'parent', null, 1, 0, 1, null],
            [196, 10, '4303', 'Food & Beverage Revenue', 'parent', null, 1, 0, 1, null],

            // Rental & Property Income
            [197, 33, '4401', 'Rental Income', 'child', 48, 1, 0, 1, null],
            [198, 10, '4402', 'Property Service Charges', 'parent', null, 1, 0, 1, null],
            [199, 11, '4403', 'Late Fee Income', 'parent', null, 1, 0, 1, null],
            [200, 10, '4404', 'Property Management Fees', 'parent', null, 1, 0, 1, null],
            // Event & decoration service specific income
            [760, 10, '4405', 'Decoration Service Income', 'parent', null, 1, 0, 1, null],
            [761, 11, '4107', 'Damage Recovery Income', 'parent', null, 1, 0, 1, null],

            // Hotel / Property Operating Expenses
            [201, 25, '5601', 'Hotel Operating Expenses', 'parent', null, 1, 0, 1, null],
            [202, 25, '5602', 'Hotel Maintenance Expenses', 'parent', null, 1, 0, 1, null],
            [203, 24, '5603', 'Hotel Marketing Expenses', 'parent', null, 1, 0, 1, null],
            [204, 29, '5604', 'Food & Beverage Costs', 'parent', null, 1, 0, 1, null],
            [205, 25, '5701', 'Property Operating Expenses', 'parent', null, 1, 0, 1, null],
            [206, 25, '5702', 'Property Maintenance Expenses', 'parent', null, 1, 0, 1, null],
            [207, 25, '5703', 'Property Utilities Expenses', 'parent', null, 1, 0, 1, null],
            [208, 25, '5704', 'Property Management Expenses', 'parent', null, 1, 0, 1, null],

            // Rental & Event Equipment specific expenses
            [764, 25, '5113', 'Loss on Equipment', 'parent', null, 1, 0, 1, null],
            [765, 25, '5610', 'Rental Equipment Operating Expenses', 'parent', null, 1, 0, 1, null],
            [766, 25, '5611', 'Rental Equipment Maintenance Expenses', 'parent', null, 1, 0, 1, null],
            [767, 25, '5612', 'Decoration Service Expenses', 'parent', null, 1, 0, 1, null],
            [768, 24, '5613', 'Event Logistics & Setup Expenses', 'parent', null, 1, 0, 1, null],

            // Transport Revenue
            [212, 11, '4205', 'Transport Revenue', 'parent', null, 1, 0, 1, null],

            // Other Fixed Assets + Accumulated Depreciation
            [600, 2, '1198', 'Other Fixed Assets', 'parent', null, 1, 0, 2, null],
            [601, 12, '1199', 'Other Fixed Assets - Accumulated Depreciation', 'parent', null, 0, 0, null, null],

            // Revaluation & Impairment
            [602, 8, '3105', 'Revaluation Reserve (IAS 16)', 'parent', null, 0, 1, null, 3],
            [603, 26, '5210', 'Impairment Expense', 'parent', null, 1, 0, 1, null],

            // Investment Properties
            [604, 30, '1555', 'Investment Properties', 'parent', null, 1, 0, 2, null],

            // Additional PPE categories
            [605, 2, '1117', 'Information Technology (ICT) Equipment', 'parent', null, 1, 0, 2, null],
            [606, 2, '1105', 'Motor Vehicles', 'parent', null, 1, 0, 2, null],
            [607, 2, '1110', 'Plant and Machinery', 'parent', null, 1, 0, 2, null],
            [608, 2, '1115', 'Tools and Equipment', 'parent', null, 1, 0, 2, null],
            [609, 2, '1120', 'Infrastructure and Improvements', 'parent', null, 1, 0, 2, null],
            [610, 2, '1125', 'Office Equipment', 'parent', null, 1, 0, 2, null],
            [611, 2, '1130', 'Medical Equipment', 'parent', null, 1, 0, 2, null],
            [612, 2, '1135', 'Leasehold Improvements', 'parent', null, 1, 0, 2, null],
            [762, 2, '1210', 'Rental & Event Equipment', 'parent', null, 1, 0, 2, null],
            [763, 2, '1211', 'Equipment Under Repair', 'parent', null, 1, 0, 2, null],

            // Intangibles
            [613, 14, '1140', 'Intangible Assets', 'parent', null, 1, 0, 2, null],

            // More PPE
            [614, 2, '1145', 'Household & Residential Equipment', 'parent', null, 1, 0, 2, null],
            [615, 2, '1150', 'Work-in-Progress (WIP) / AUC', 'parent', null, 1, 0, 2, null],
            [616, 2, '1155', 'Library & Learning Resources', 'parent', null, 1, 0, 2, null],
            [617, 2, '1166', 'Agricultural & Biological Assets', 'parent', null, 1, 0, 2, null],
            [618, 2, '1175', 'Energy & Utility Assets', 'parent', null, 1, 0, 2, null],
            [619, 2, '1177', 'Donor-funded Assets', 'parent', null, 1, 0, 2, null],
            [620, 2, '1179', 'Heritage or Cultural Assets', 'parent', null, 1, 0, 2, null],

            // Accumulated Depreciation (various)
            [621, 12, '1106', 'Motor Vehicles - Accumulated Depreciation', 'parent', null, 0, 0, null, null],
            [622, 12, '1111', 'Plant and Machinery -  Accumulated Depreciation', 'parent', null, 0, 0, null, null],
            [623, 12, '1118', 'ICT Equipment - Accumulated Depreciation', 'parent', null, 0, 0, null, null],
            [624, 12, '1121', 'Infrastructure and Improvements - Accumulated Depreciation', 'parent', null, 0, 0, null, null],
            [625, 12, '1126', 'Office Equipment - Accumulated Depreciation', 'parent', null, 0, 0, null, null],
            [626, 12, '1131', 'Medical Equipment - Accumulated Depreciation', 'parent', null, 0, 0, null, null],
            [627, 12, '1136', 'Leasehold Improvements - Accumulated Depreciation', 'parent', null, 0, 0, null, null],
            [628, 12, '1141', 'Intangible Assets - Accumulated Amortization', 'parent', null, 0, 0, null, null],
            [629, 12, '1146', 'Household & Residential Equipment - Accumulated Depreciation', 'parent', null, 0, 0, null, null],
            [630, 12, '1209', 'Buildings - Accumulated Depreciation', 'parent', null, 0, 0, null, null],
            [631, 12, '1116', 'Tools and Equipment - Accumulated Depreciation', 'parent', null, 0, 0, null, null],
            [632, 12, '1178', 'Donor-funded Assets - Accumulated Depreciation', 'parent', null, 0, 0, null, null],
            [633, 12, '1176', 'Energy & Utility Assets - Accumulated Depreciation', 'parent', null, 0, 0, null, null],
            [634, 12, '1167', 'Agricultural & Biological Assets - Accumulated Depreciation', 'parent', null, 0, 0, null, null],
            [635, 12, '1156', 'Library & Learning Resources - Accumulated Depreciation', 'parent', null, 0, 0, null, null],

            // Deferred Tax
            [636, 11, '4100', 'Fair Value Gain / Loss on Investment Properties', 'child', 48, 1, 0, 1, null],
            [637, 25, '5115', 'Revaluation Loss on Fixed Assets', 'parent', null, 1, 0, 1, null],
            [638, 28, '5112', 'Deferred Tax Movement', 'parent', null, 0, 0, null, null],
            [639, 12, '1335', 'Deferred Tax Asset', 'parent', null, 0, 0, null, null],
            [640, 20, '2115', 'Deferred Tax Liability', 'parent', null, 0, 0, null, null],

            // Withholding Tax Receivable
            [641, 1, '1245', 'Withholding Tax Receivable', 'parent', null, 1, 0, 1, null],

            // Main Bank Accounts, Mobile Money
            [642, 16, '1122', 'Main Bank Accounts', 'parent', null, 1, 0, 4, null],
            [643, 16, '1124', 'Mobile Money Account', 'parent', null, 1, 0, 4, null],

            // ECL Allowance
            [644, 1, '1123', 'Allowance for Expected Credit Loss (IFRS 9)', 'parent', null, 0, 0, null, null],

            // More Inventory
            [645, 17, '1128', 'Inventory Work in Progress - WIP', 'parent', null, 1, 0, 1, null],
            [646, 17, '1129', 'Spare Parts Inventory', 'parent', null, 1, 0, 1, null],
            [647, 17, '1133', 'Goods in Transit - GIT', 'parent', null, 1, 0, 1, null],

            // More Prepayments
            [648, 18, '1134', 'Other Prepayments', 'parent', null, 1, 0, 1, null],
            [649, 18, '1336', 'Advances to Suppliers', 'parent', null, 1, 0, 1, null],

            // Fair Value Reserve (IFRS 9)
            [650, 8, '3124', 'Fair Value Reserve (IFRS 9)', 'parent', null, 0, 1, null, 3],

            // Dividends
            [651, 9, '3120', 'Dividends Declared – Payable', 'parent', null, 1, 1, null, 2],
            [652, 9, '3125', 'Dividends Paid', 'parent', null, 1, 1, null, 2],

            // Borrowings / Lease Liabilities / Deferred Revenue
            [653, 6, '2345', 'Long-term Loan', 'parent', null, 1, 0, null, null],
            [654, 6, '2440', 'Bonds Payable', 'parent', null, 1, 0, null, null],
            [655, 19, '2435', 'Lease Liability – Building', 'parent', null, 1, 0, null, null],
            [656, 19, '2140', 'Lease Liability – Vehicle', 'parent', null, 1, 0, null, null],
            [657, 20, '2348', 'Deferred Revenue – Long-term (IFRS 15)', 'parent', null, 0, 0, null, null],

            // Payroll Taxes etc.
            [658, 5, '2349', 'Fringe Benefit Tax Payable', 'parent', null, 1, 0, 1, null],
            [659, 5, '2125', 'PAYE Payable', 'parent', null, 1, 0, 1, null],
            [660, 5, '2120', 'SDL Payable', 'parent', null, 1, 0, 1, null],
            [661, 5, '2146', 'WCF Payable', 'parent', null, 1, 0, 1, null],
            [662, 5, '2123', 'NHIF Payable', 'parent', null, 1, 0, 1, null],
            [663, 5, '2333', 'Trade Union Payable', 'parent', null, 1, 0, 1, null],
            [664, 5, '2222', 'Loan Deductions Payable', 'parent', null, 1, 0, 1, null],

            // Short-term Borrowings
            [665, 21, '2131', 'Bank Overdraft', 'parent', null, 1, 0, 4, null],
            [666, 21, '2121', 'Current Portion of Long-Term Loan', 'parent', null, 1, 0, null, null],

            // Provisions
            [667, 22, '2312', 'Provision for Legal Claims', 'parent', null, 1, 0, 1, null],
            [668, 22, '2445', 'Provision for Bonuses', 'parent', null, 1, 0, 1, null],
            [669, 22, '2545', 'Provision for Warranty', 'parent', null, 1, 0, 1, null],
            [670, 22, '2224', 'Provisions for Audit Fees', 'parent', null, 1, 0, 1, null],

            // Advance Receipts, Unearned Revenue, Suspense
            [671, 23, '2236', 'Advance Receipts', 'parent', null, 1, 0, 1, null],
            [672, 23, '2355', 'Unearned Revenue', 'parent', null, 1, 0, 1, null],
            [673, 23, '2111', 'Suspense Account', 'parent', null, 1, 0, 1, null],

            // Additional Revenue
            [674, 10, '4115', 'Sales – Services', 'parent', null, 1, 0, 1, null],
            [675, 10, '4159', 'Commission Income', 'parent', null, 1, 0, 1, null],
            [676, 10, '4123', 'Export Sales', 'parent', null, 1, 0, 1, null],

            // More Cost of Sales
            [677, 29, '5678', 'Direct Labour', 'parent', null, 1, 0, 1, null],
            [678, 29, '5116', 'Direct Material', 'parent', null, 1, 0, 1, null],
            [679, 29, '5123', 'Production Overheads', 'parent', null, 1, 0, 1, null],

            // Selling & Distribution
            [680, 24, '5127', 'Advertising & Promotion', 'parent', null, 1, 0, 1, null],
            [681, 24, '5111', 'Freight Outwards', 'parent', null, 1, 0, 1, null],
            [682, 24, '5222', 'Sales Commissions', 'parent', null, 1, 0, 1, null],

            // Depreciation – ROU, Interest on Leases
            [683, 26, '5555', 'Depreciation – ROU Asset (IFRS 16)', 'parent', null, 1, 0, 1, null],
            [684, 27, '5444', 'Interest Expense – Lease Liabilities', 'parent', null, 1, 0, 1, null],

            // Financial Assets Investments
            [685, 31, '1785', 'Treasury Bonds', 'parent', null, 1, 0, 2, null],

            // Share Premium
            [686, 8, '3530', 'Share Premium', 'parent', null, 1, 1, null, 1],

            // Short-term Investments
            [687, 1, '1675', 'Short-term Loan Investments', 'parent', null, 1, 0, 2, null],
            [688, 1, '1453', 'Treasury Bills > 3 Months', 'parent', null, 1, 0, 2, null],
            [689, 16, '1670', 'Treasury Bills <= 3 Months', 'parent', null, 1, 0, 4, null],

            // More Investment Income
            [690, 33, '4320', 'Dividend Income', 'child', 48, 1, 0, 2, null],

            // Allowance for Inventory Obsolescence
            [691, 17, '1171', 'Allowance for Inventory Obsolescence', 'child', 185, 0, 0, null, null],

            // FX Gains/Losses Unrealized
            [692, 11, '4125', 'Foreign Exchange Gain - Unrealized', 'parent', null, 1, 0, 1, null],
            [693, 25, '5600', 'Foreign Exchange Loss - Unrealized', 'parent', null, 1, 0, 1, null],

            // Accrued Income
            [694, 1, '1800', 'Accrued Income', 'parent', null, 1, 0, 1, null],

            // More Employee Benefits
            [695, 32, '5124', 'SDL Expenses', 'parent', null, 1, 0, 1, null],
            [696, 32, '5226', 'Social Security Costs', 'parent', null, 1, 0, 1, null],
            [697, 32, '5119', 'Overtime Payments', 'parent', null, 1, 0, 1, null],
            [698, 32, '5125', 'Leave Pay', 'parent', null, 1, 0, 1, null],
            [699, 32, '5167', 'Bonus & Incentives', 'parent', null, 1, 0, 1, null],
            [700, 32, '5540', 'Allowances (Transport, Housing, etc.)', 'parent', null, 1, 0, 1, null],
            [701, 32, '5146', 'Allowances (Transport, Housing, etc.)', 'parent', null, 1, 0, 1, null],
            [702, 32, '5122', 'WCF Contribution Cost', 'parent', null, 1, 0, 1, null],
            [703, 32, '5128', 'Medical & Insurance Benefits', 'parent', null, 1, 0, 1, null],
            [751, 32, '5466', 'NHIF / Insurance Expenses', 'parent', null, 1, 0, 1, null],
            [704, 32, '5334', 'Staff Meals & Welfare', 'parent', null, 1, 0, 1, null],
            [705, 32, '5370', 'Training & Development Costs', 'parent', null, 1, 0, 1, null],
            [706, 32, '5450', 'Uniforms & Staff Protective Gear', 'parent', null, 1, 0, 1, null],

            // Interest Payable
            [708, 5, '2546', 'Grants and Donations', 'parent', null, 1, 0, null, null],

            // More Investments
            [709, 31, '1654', 'Investment in Equity Shares', 'parent', null, 1, 0, 2, null],
            [710, 31, '1674', 'Corporate Bonds Investment', 'parent', null, 1, 0, 2, null],
            [711, 31, '1634', 'Long-term Loan Investments', 'parent', null, 1, 0, 2, null],

            // Interest Payable (standalone accounts since parent 707 was removed)
            [712, 23, '2674', 'Interest payable - Bank Loan', 'parent', null, 1, 0, 1, null],
            [713, 23, '2712', 'Interest payable - Lease Liabilities', 'parent', null, 1, 0, 1, null],

            // Inventory Shrinkage / Losses
            [714, 29, '5342', 'Inventory Shrinkage / Losses', 'parent', null, 1, 0, 1, null],

            // Impairment Losses by Asset Class
            [715, 12, '1263', 'Accum. Impairment Loss – Investment Properties (IAS 40)', 'parent', null, 0, 0, null, null],
            [716, 12, '1652', 'Accum. Impairment Loss – Furniture, Fixtures & Fittings', 'parent', null, 0, 0, null, null],
            [717, 12, '1587', 'Accum. Impairment Loss – Intangibles (IAS 36)', 'parent', null, 0, 0, null, null],
            [718, 12, '1658', 'Accum. Impairment Loss – Investments (IFRS 9)', 'parent', null, 0, 0, null, null],

            // Fair Value Gain/Loss – Financial Instruments
            [719, 11, '4127', 'Fair Value Gain/Loss – Financial Instruments (IFRS 9)', 'parent', null, 1, 0, 1, null],

            // Direct Operating Expenses – Investment Properties
            [720, 29, '5234', 'Direct Operating Expenses – Investment Properties', 'parent', null, 1, 0, 1, null],

            // ROU Asset
            [721, 2, '1657', 'Right-of-Use Asset (ROUA)', 'parent', null, 1, 0, 2, null],

            // Impairment Reversal / HFS Gains & Losses
            [722, 11, '4567', 'Impairment Reversal Account', 'parent', null, 1, 0, 1, null],
            [723, 34, '1349', 'PPE Held for Sale', 'parent', null, 1, 0, 1, null],
            [724, 34, '1350', 'Intangible Assets Held for Sale', 'parent', null, 1, 0, 1, null],
            [725, 34, '1351', 'Investment Property Held for Sale', 'parent', null, 1, 0, 1, null],
            [726, 34, '1352', 'Accumulated Impairment – Held for Sale', 'parent', null, 0, 0, null, null],
            [727, 12, '1450', 'Accum. Impairment - Office Equipment', 'parent', null, 0, 0, null, null],
            [728, 12, '1455', 'Accum. Impairment - Plant and Machinery', 'parent', null, 0, 0, null, null],
            [729, 12, '1460', 'Accum. Impairment - Motor Vehicles', 'parent', null, 0, 0, null, null],
            [730, 12, '1465', 'Accum. Impairment - Buildings', 'parent', null, 0, 0, null, null],
            [731, 12, '1468', 'Accum. Impairment - 	Land', 'parent', null, 0, 0, null, null],
            [732, 12, '1469', 'Accum. Impairment - 		Hotel Equipment', 'parent', null, 0, 0, null, null],
            [733, 12, '1470', 'Accum. Impairment - 	Other Fixed Assets', 'parent', null, 0, 0, null, null],
            [734, 12, '1475', 'Accum. Impairment - Heritage or Cultural Assets', 'parent', null, 0, 0, null, null],
            [735, 12, '1476', 'Accum. Impairment - Donor-funded Assets', 'parent', null, 0, 0, null, null],
            [736, 12, '1479', 'Accum. Impairment - Energy & Utility Assets', 'parent', null, 0, 0, null, null],
            [737, 12, '1480', 'Accum. Impairment - Agricultural & Biological Assets', 'parent', null, 0, 0, null, null],
            [738, 12, '1485', 'Accum. Impairment - Library & Learning Resources', 'parent', null, 0, 0, null, null],
            [739, 12, '1488', 'Accum. Impairment - Household & Residential Equipment', 'parent', null, 0, 0, null, null],
            [740, 12, '1490', 'Accum. Impairment - Medical Equipment', 'parent', null, 0, 0, null, null],
            [741, 12, '1495', 'Accum. Impairment - Infrastructure and Improvements', 'parent', null, 0, 0, null, null],
            [742, 12, '1500', 'Accum. Impairment - Information Technology (ICT) Equipment', 'parent', null, 0, 0, null, null],
            [743, 12, '1505', 'Accum. Impairment - Tools and Equipment', 'parent', null, 0, 0, null, null],
            [744, 12, '1510', 'Accum. Impairment - Leasehold Improvements', 'parent', null, 0, 0, null, null],

            // HFS Impairment & Disposal
            [745, 25, '5235', 'Impairment Loss – NCA Held for Sale', 'parent', null, 0, 0, null, null],
            [746, 11, '4345', 'Gain on Re-measurement – NCA HFS (Impairment Reversal)', 'parent', null, 0, 0, null, null],
            [747, 11, '4350', 'Gain on Disposal of NCA HFS', 'parent', null, 0, 0, null, null],
            [748, 25, '5435', 'Loss on Disposal of NCA HFS', 'parent', null, 0, 0, null, null],
        ];

        // Insert parents first (including any accounts with null parent_id),
        // then children, to satisfy the self-referencing FK on parent_id.
        $parents = [];
        $children = [];

        foreach ($accounts as $account) {
            if ($account[5] === null) {
                $parents[] = $account;
            } else {
                $children[] = $account;
            }
        }

        foreach ([$parents, $children] as $batch) {
            foreach ($batch as $account) {
                [
                    $id,
                    $logicalGroupId,
                    $code,
                    $name,
                    $type,
                    $parentId,
                    $hasCashFlow,
                    $hasEquity,
                    $cashFlowCategoryId,
                    $equityCategoryId,
                ] = $account;

                // Resolve actual account_class_group_id from logical group id
                $groupId = $resolvedGroupIds[$logicalGroupId] ?? null;

                // If we can't resolve the group, skip this account to avoid FK errors.
                if (! $groupId) {
                    continue;
                }

                // Only keep cash_flow_category_id / equity_category_id if the
                // referenced records actually exist, otherwise set to null to
                // avoid foreign key violations during seeding.
                if ($cashFlowCategoryId !== null) {
                    $exists = DB::table('cash_flow_categories')
                        ->where('id', $cashFlowCategoryId)
                        ->exists();
                    if (! $exists) {
                        $cashFlowCategoryId = null;
                    }
                }

                if ($equityCategoryId !== null) {
                    $exists = DB::table('equity_categories')
                        ->where('id', $equityCategoryId)
                        ->exists();
                    if (! $exists) {
                        $equityCategoryId = null;
                    }
                }

                DB::table('chart_accounts')->insert([
                    'id' => $id,
                    'account_class_group_id' => $groupId,
                    'account_code' => $code,
                    'account_name' => $name,
                    'account_type' => $type,
                    'parent_id' => $parentId,
                    'has_cash_flow' => $hasCashFlow,
                    'has_equity' => $hasEquity,
                    'cash_flow_category_id' => $cashFlowCategoryId,
                    'equity_category_id' => $equityCategoryId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }
}

