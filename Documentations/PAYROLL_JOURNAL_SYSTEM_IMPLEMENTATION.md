# Payroll Journal Entry System Implementation

## Overview
This document outlines the complete double-entry accounting system implemented for payroll processing, including accrual journal entries and payment processing.

## System Architecture

### 1. Database Schema Updates

#### New Fields Added to Payrolls Table:
- `reference` - Unique payroll reference number (PAY-YYYY-MM-XXXX)
- `journal_reference` - Reference to accrual journal entry
- `payment_status` - Enum: 'pending', 'paid'
- `payment_reference` - External payment reference (bank transfer, cheque, etc.)
- `payment_journal_reference` - Reference to payment journal entry

#### Payroll Chart Accounts Configuration:
Extended with salary-specific accounts:
- `salary_payable_account_id` - For net salary liability
- `salary_expense_account_id` - For salary expenses

## Workflow Implementation

### Phase 1: After Final Approval (Accrual Entry)
When a payroll reaches final approval, the system automatically creates:

#### Accrual Journal Entry Structure:
```
DEBIT Accounts:
- Salary Expense Account: Basic salary + allowances
- Allowance Expense Account: Allowances (if separate account)
- PAYE Expense Account: Income tax expenses
- Pension Expense Account: Pension contributions
- Insurance Expense Account: Insurance deductions
- Trade Union Expense Account: Union dues
- SDL Expense Account: Skills Development Levy
- WCF Expense Account: Workers Compensation Fund
- HESLB Expense Account: Student loan deductions

CREDIT Accounts:
- Salary Payable Account: Net salary (gross - all deductions)
- PAYE Payable Account: Income tax liability
- Pension Payable Account: Pension contributions liability
- Insurance Payable Account: Insurance premiums liability
- Trade Union Payable Account: Union dues liability
- SDL Payable Account: SDL liability
- WCF Payable Account: WCF liability
- HESLB Payable Account: Student loan liability
- Salary Advance Receivable Account: Advance recoveries
- Other Payable Account: Other deductions
```

### Phase 2: Payment Processing
When salary payment is processed:

#### Payment Journal Entry Structure:
```
DEBIT: Salary Payable Account (clears the liability)
CREDIT: Bank Account (cash goes out)
```

## Code Implementation

### Key Controller Methods:

#### 1. `createPayrollAccrualJournalEntry(Payroll $payroll)`
- Creates comprehensive accrual entries for all payroll components
- Handles salary expenses, statutory deductions, and payables
- Uses bulk insert for performance with large payrolls
- Integrates with both `journals`, `journal_items`, and `gl_transactions` tables

#### 2. `showPaymentForm(Payroll $payroll)`
- Displays payment form with bank account selection
- Shows payment summary and journal entry preview
- Validates payroll status and chart account configuration

#### 3. `processPayment(Request $request, Payroll $payroll)`
- Processes salary payment with proper journal entries
- Updates payroll payment status and references
- Creates audit trail for payment tracking

### Key Features:

#### Automatic Journal Creation:
- Triggered after final approval in multi-level workflow
- Creates complete double-entry accounting records
- Maintains referential integrity between payroll and journal records

#### Payment Processing:
- Dedicated payment form with bank account selection
- Real-time journal entry preview
- Complete audit trail with payment references

#### Error Handling:
- Validates chart account configuration
- Prevents duplicate payments
- Rolls back transactions on failure

## Security & Validation

### Authorization:
- Payment processing restricted to completed payrolls
- Chart account configuration validation
- User permission checks for payment processing

### Data Integrity:
- Database transactions ensure consistency
- Foreign key constraints maintain referential integrity
- Status validation prevents invalid state transitions

## Integration Points

### Multi-Level Approval Workflow:
- Journal entries created only after final approval
- Status progression: Draft → Processing → Completed → Paid
- Approval tracking with individual approver records

### Chart of Accounts:
- Flexible account mapping configuration
- Support for different expense and payable accounts
- Automatic account validation during processing

### Audit Trail:
- Complete transaction logging with `activity` package
- Payment reference tracking
- User action tracking with timestamps

## Usage Flow

### 1. Payroll Creation:
```
1. Create payroll (Draft status)
2. Process payroll (generates employee records)
3. Submit for approval (Processing status)
```

### 2. Approval Workflow:
```
1. Level 1 approval
2. Level 2 approval (if configured)
3. Final approval triggers accrual journal entry
4. Status changes to "Completed"
```

### 3. Payment Processing:
```
1. Access payment form from completed payroll
2. Select bank account and enter payment details
3. Process payment (creates payment journal entry)
4. Status changes to "Paid"
```

## Technical Specifications

### Database Tables Used:
- `payrolls` - Main payroll records with status tracking
- `hr_payroll_chart_accounts` - Account mapping configuration
- `journals` - Journal header records
- `journal_items` - Individual debit/credit entries
- `gl_transactions` - General ledger transaction records

### Key Relationships:
- Payroll → Journal (one-to-many for accrual and payment)
- Journal → JournalItems (one-to-many)
- JournalItems → ChartAccounts (many-to-one)
- GlTransaction → ChartAccounts (many-to-one)

### Performance Considerations:
- Bulk insert operations for large payrolls
- Indexed foreign keys for efficient queries
- Transaction boundaries to ensure consistency

## Configuration Requirements

### Chart Accounts Setup:
Before using the payroll system, configure the following accounts:
1. Navigate to HR Payroll → Chart Accounts Settings
2. Map all required accounts for expenses and payables
3. Ensure salary payable account is properly configured

### Approval Workflow:
1. Configure approval levels and approvers
2. Set approval amount thresholds
3. Test workflow with sample payroll

This implementation provides a complete, auditable, and compliant payroll accounting system that integrates seamlessly with the existing multi-level approval workflow.