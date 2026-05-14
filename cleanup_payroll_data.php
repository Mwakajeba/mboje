<?php

/**
 * PAYROLL DATA CLEANUP SCRIPT
 * 
 * This script deletes all payroll entries and related data from the database
 * while preserving payroll settings and employee records.
 * 
 * DELETES:
 * - Payroll records
 * - Payroll employees (assignment records)
 * - Payroll approvals
 * - Payroll journals (accrual and payment)
 * - Payroll journal items
 * - Payroll GL transactions
 * - Payroll payments
 * 
 * PRESERVES:
 * - Payroll approval settings
 * - Payroll chart account settings
 * - Employee records
 * - Chart of accounts
 * - All other system data
 * 
 * USAGE:
 * php cleanup_payroll_data.php
 * 
 * WARNING: This operation cannot be undone!
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Payroll;
use App\Models\PayrollEmployee;
use App\Models\PayrollApproval;
use App\Models\Journal;
use App\Models\JournalItem;
use App\Models\GlTransaction;
use App\Models\Payment;

echo "\n";
echo "==============================================\n";
echo "  PAYROLL DATA CLEANUP UTILITY\n";
echo "==============================================\n\n";

// Count records to be deleted
echo "📊 Analyzing data to be deleted...\n\n";

$counts = [
    'payrolls' => Payroll::count(),
    'payroll_employees' => PayrollEmployee::count(),
    'payroll_approvals' => PayrollApproval::count(),
    'payroll_journals' => Journal::whereIn('reference_type', ['payroll_accrual', 'payroll_payment'])->count(),
    'payroll_journal_items' => JournalItem::whereHas('journal', function($q) {
        $q->whereIn('reference_type', ['payroll_accrual', 'payroll_payment']);
    })->count(),
    'payroll_gl_transactions' => GlTransaction::whereIn('transaction_type', ['payroll_accrual', 'payroll_payment'])->count(),
    'payroll_payments' => Payment::where('reference_type', 'payroll')->count(),
];

// Display summary
echo "Data to be deleted:\n";
echo "- Payroll Records: " . $counts['payrolls'] . "\n";
echo "- Payroll Employees: " . $counts['payroll_employees'] . "\n";
echo "- Payroll Approvals: " . $counts['payroll_approvals'] . "\n";
echo "- Payroll Journals: " . $counts['payroll_journals'] . "\n";
echo "- Payroll Journal Items: " . $counts['payroll_journal_items'] . "\n";
echo "- Payroll GL Transactions: " . $counts['payroll_gl_transactions'] . "\n";
echo "- Payroll Payments: " . $counts['payroll_payments'] . "\n";

$totalRecords = array_sum($counts);

echo "\nTotal records to be deleted: {$totalRecords}\n\n";

if ($totalRecords === 0) {
    echo "✅ No payroll data found to delete.\n";
    exit(0);
}

// Preserved data
echo "✅ DATA THAT WILL BE PRESERVED:\n";
echo "   - Payroll Approval Settings\n";
echo "   - Payroll Chart Account Settings\n";
echo "   - Employee Records\n";
echo "   - Chart of Accounts\n";
echo "   - All other system data\n\n";

// Confirmation
echo "⚠️  WARNING: This action cannot be undone!\n\n";
echo "Type 'DELETE' to confirm deletion: ";
$confirmation = trim(fgets(STDIN));

if ($confirmation !== 'DELETE') {
    echo "❌ Operation cancelled.\n";
    exit(1);
}

echo "\n🗑️  Starting deletion process...\n\n";

DB::beginTransaction();

try {
    // Step 1: Delete payroll-related payments
    echo "Deleting payroll payments... ";
    $deletedPayments = Payment::where('reference_type', 'payroll')->delete();
    echo "✓ Deleted {$deletedPayments} records\n";

    // Step 2: Delete payroll approvals
    echo "Deleting payroll approvals... ";
    $deletedApprovals = PayrollApproval::query()->delete();
    echo "✓ Deleted {$deletedApprovals} records\n";

    // Step 3: Delete payroll employees
    echo "Deleting payroll employees... ";
    $deletedEmployees = PayrollEmployee::query()->delete();
    echo "✓ Deleted {$deletedEmployees} records\n";

    // Step 4: Delete payroll journal items
    echo "Deleting payroll journal items... ";
    $journalIds = Journal::whereIn('reference_type', ['payroll_accrual', 'payroll_payment'])
        ->pluck('id')
        ->toArray();
    
    $deletedJournalItems = 0;
    if (!empty($journalIds)) {
        $deletedJournalItems = JournalItem::whereIn('journal_id', $journalIds)->delete();
    }
    echo "✓ Deleted {$deletedJournalItems} records\n";

    // Step 5: Delete payroll GL transactions
    echo "Deleting payroll GL transactions... ";
    $deletedGLTransactions = GlTransaction::whereIn('transaction_type', ['payroll_accrual', 'payroll_payment'])
        ->delete();
    echo "✓ Deleted {$deletedGLTransactions} records\n";

    // Step 6: Delete payroll journals
    echo "Deleting payroll journals... ";
    $deletedJournals = Journal::whereIn('reference_type', ['payroll_accrual', 'payroll_payment'])
        ->delete();
    echo "✓ Deleted {$deletedJournals} records\n";

    // Step 7: Delete payroll records
    echo "Deleting payroll records... ";
    $deletedPayrolls = Payroll::query()->delete();
    echo "✓ Deleted {$deletedPayrolls} records\n";

    DB::commit();

    echo "\n";
    echo "==============================================\n";
    echo "✅ CLEANUP COMPLETED SUCCESSFULLY\n";
    echo "==============================================\n\n";
    
    echo "Summary of deleted records:\n";
    echo "- Payroll Records: {$deletedPayrolls}\n";
    echo "- Payroll Employees: {$deletedEmployees}\n";
    echo "- Payroll Approvals: {$deletedApprovals}\n";
    echo "- Payroll Journals: {$deletedJournals}\n";
    echo "- Payroll Journal Items: {$deletedJournalItems}\n";
    echo "- Payroll GL Transactions: {$deletedGLTransactions}\n";
    echo "- Payroll Payments: {$deletedPayments}\n";
    
    $total = $deletedPayrolls + $deletedEmployees + $deletedApprovals + 
             $deletedJournals + $deletedJournalItems + $deletedGLTransactions + $deletedPayments;
    echo "\nTOTAL DELETED: {$total} records\n\n";

    echo "✅ Payroll approval settings and employee records preserved.\n\n";

} catch (\Exception $e) {
    DB::rollBack();

    echo "\n";
    echo "==============================================\n";
    echo "❌ CLEANUP FAILED\n";
    echo "==============================================\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n\n";
    echo "⚠️  Transaction rolled back. No data was deleted.\n\n";
    
    exit(1);
}
