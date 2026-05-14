# Petty Cash Management System - Complete Flow Documentation

## Table of Contents
1. [System Overview](#system-overview)
2. [Initial Setup](#initial-setup)
3. [Daily Operations](#daily-operations)
4. [Replenishment Process](#replenishment-process)
5. [General Ledger Integration](#general-ledger-integration)
6. [Approval Workflows](#approval-workflows)
7. [Reporting and Monitoring](#reporting-and-monitoring)
8. [Best Practices](#best-practices)

---

## System Overview

The Petty Cash Management System is designed to manage small, day-to-day cash expenses efficiently while maintaining proper accounting controls and audit trails. The system supports multiple petty cash units, automated GL posting, approval workflows, and comprehensive reporting.

### Key Features
- **Multiple Petty Cash Units**: Support for different branches or departments
- **Automated GL Posting**: Automatic double-entry accounting
- **Approval Workflows**: Configurable approval thresholds
- **Replenishment Management**: Automated replenishment requests and processing
- **Real-time Balance Tracking**: Current balance monitoring with limits
- **Comprehensive Reporting**: Transaction history, balance reports, and audit trails

---

## Initial Setup

### 1. Create Petty Cash Unit

**Purpose**: Establish a new petty cash fund with initial float amount.

**Steps**:
1. Navigate to **Accounting Management → Petty Cash Units → Create New Unit**
2. Fill in the required information:
   - **Unit Name**: Descriptive name (e.g., "HQ Petty Cash", "Branch A Petty Cash")
   - **Unit Code**: Unique identifier (e.g., "PC-HQ", "PC-BR-A")
   - **Branch**: Select branch if unit is branch-specific (optional)
   - **Custodian**: Person responsible for managing the petty cash
   - **Supervisor**: Person who approves replenishments and large expenses
   - **Float Amount**: Initial cash amount for the unit
   - **Maximum Limit**: Maximum allowed balance (optional)
   - **Approval Threshold**: Amount above which supervisor approval is required
   - **Petty Cash Account**: GL account for petty cash asset
   - **Bank Account**: Bank account from which the float will be debited

**What Happens Behind the Scenes**:
- Petty cash unit record is created
- Current balance is set equal to float amount
- **Automatic GL Double Entry**:
  - **Debit**: Petty Cash Account (increases petty cash asset)
  - **Credit**: Bank Account (decreases bank balance)
- Journal entry is created with reference type "Petty Cash Unit Setup"
- Journal goes through approval workflow (creates GL transactions if auto-approved)

**Accounting Impact**:
```
Dr. Petty Cash Account          XXX
    Cr. Bank Account                    XXX
```

---

## Daily Operations

### 2. Create Petty Cash Transaction

**Purpose**: Record daily expenses from petty cash.

**Steps**:
1. Navigate to **Petty Cash Unit → Show → New Transaction** (or use Transactions menu)
2. Fill in transaction details:
   - **Transaction Date**: Date of expense
   - **Description**: Brief description of the expense
   - **Payee Type**: Select Customer, Supplier, or Expense
   - **Payee**: Select the payee (if applicable)
   - **Expense Line Items**: Add one or more expense items:
     - **Account**: Select GL expense account
     - **Description**: Item description
     - **Amount**: Expense amount
   - **Receipt Attachment**: Upload receipt (PDF, JPG, PNG)
   - **Notes**: Additional notes (optional)

**Approval Requirements**:
- If transaction amount ≤ **Approval Threshold**: Auto-approved and posted to GL immediately
- If transaction amount > **Approval Threshold**: Status set to "submitted", requires supervisor approval

**What Happens Behind the Scenes**:
- Transaction record is created
- If auto-approved:
  - **Payment** record is created (reference_type = 'petty_cash')
  - **Payment Items** are created for each expense line
  - **GL Transactions** are created:
    - **Debit**: Expense Account(s) (one for each line item)
    - **Credit**: Petty Cash Account
  - Current balance is reduced by transaction amount
  - Balance after is calculated and stored
- If requires approval:
  - Status set to "submitted"
  - Balance remains unchanged until approved

**Accounting Impact (Auto-Approved)**:
```
Dr. Expense Account(s)          XXX
    Cr. Petty Cash Account              XXX
```

**Accounting Impact (After Approval)**:
- Same as above, but GL posting happens when transaction is approved

### 3. Approve Transaction

**Purpose**: Approve transactions that exceed the approval threshold.

**Steps**:
1. Navigate to transaction list or unit details page
2. Click **Approve** button on submitted transactions
3. Confirm approval (SweetAlert confirmation)

**What Happens Behind the Scenes**:
- Transaction status changes to "approved"
- **Payment** record is created (if not already created)
- **Payment Items** are created for each expense line
- **GL Transactions** are created:
  - **Debit**: Expense Account(s)
  - **Credit**: Petty Cash Account
- Current balance is reduced
- Balance after is updated

### 4. Post Transaction to GL (Manual)

**Purpose**: Manually post approved transactions to GL (if not auto-posted).

**Steps**:
1. Navigate to transaction details
2. Click **Post to GL** button
3. Confirm posting

**What Happens Behind the Scenes**:
- Same GL posting logic as approval
- Creates Payment, PaymentItems, and GL Transactions
- Updates balance

---

## Replenishment Process

### 5. Request Replenishment

**Purpose**: Request additional funds when petty cash balance is low.

**Steps**:
1. Navigate to **Petty Cash Unit → Show → Request Replenishment**
2. Fill in replenishment details:
   - **Request Date**: Date of request
   - **Requested Amount**: Amount needed to restore float
   - **Source Bank Account**: Bank account from which funds will be transferred
   - **Reason**: Reason for replenishment
   - **Notes**: Additional notes (optional)

**What Happens Behind the Scenes**:
- Replenishment record is created with status "draft" or "submitted"
- If approval is required, status is set to "submitted"
- Replenishment number is auto-generated (format: PCR-YYYYMM-####)

### 6. Approve Replenishment

**Purpose**: Approve replenishment requests.

**Steps**:
1. Navigate to replenishment list or unit details page
2. Click **Approve** button on submitted replenishments
3. Confirm approval

**What Happens Behind the Scenes**:
- Replenishment status changes to "approved"
- Approved amount is set (may differ from requested amount)
- Approved by and approved date are recorded

### 7. Post Replenishment to GL

**Purpose**: Post approved replenishment to GL, transferring funds from bank to petty cash.

**Steps**:
1. Navigate to replenishment details
2. Click **Post to GL** button
3. Confirm posting

**What Happens Behind the Scenes**:
- **Journal** entry is created with reference type "Petty Cash Replenishment"
- **Journal Items** are created:
  - **Debit**: Petty Cash Account (increases petty cash)
  - **Credit**: Bank Account (decreases bank balance)
- Journal goes through approval workflow
- Current balance is increased by approved amount
- Balance after is updated

**Accounting Impact**:
```
Dr. Petty Cash Account          XXX
    Cr. Bank Account                    XXX
```

---

## General Ledger Integration

### Automatic GL Posting

The system automatically creates GL entries for:

1. **Petty Cash Unit Setup**:
   - Creates Journal entry when unit is created
   - Debits Petty Cash Account, Credits Bank Account

2. **Petty Cash Transactions**:
   - Creates Payment and PaymentItems records
   - Creates GL Transactions:
     - Debits Expense Account(s)
     - Credits Petty Cash Account
   - Posts immediately if auto-approved, or after approval

3. **Petty Cash Replenishments**:
   - Creates Journal entry when replenishment is posted
   - Debits Petty Cash Account, Credits Bank Account

### GL Transaction Details

All GL transactions include:
- **Transaction Type**: 'petty_cash' or 'journal'
- **Reference Type**: 'Petty Cash Transaction' or 'Petty Cash Replenishment'
- **Reference ID**: Links to transaction or replenishment
- **Date**: Transaction date
- **Description**: Detailed description
- **Branch ID**: Branch associated with the transaction
- **User ID**: User who created the transaction

---

## Approval Workflows

### Transaction Approval

**Automatic Approval**:
- Transactions ≤ Approval Threshold are auto-approved
- GL posting happens immediately
- Balance is updated immediately

**Manual Approval**:
- Transactions > Approval Threshold require supervisor approval
- Supervisor can approve from transaction list or details page
- After approval, GL posting occurs automatically
- Balance is updated

### Replenishment Approval

**Workflow**:
1. Replenishment is created (status: draft or submitted)
2. Supervisor reviews and approves
3. Approved amount may differ from requested amount
4. After approval, replenishment can be posted to GL
5. GL posting transfers funds from bank to petty cash

### Approval Settings

- **Approval Threshold**: Set per unit, determines when approval is required
- **Supervisor**: Assigned per unit, approves transactions and replenishments
- **Multi-level Approval**: Can be configured in system settings (future enhancement)

---

## Reporting and Monitoring

### Available Reports

1. **Petty Cash Units Index**:
   - List of all petty cash units
   - Current balance, float amount, status
   - Filter by branch
   - Export capabilities

2. **Transaction History**:
   - All transactions for a unit or across units
   - Filter by date range, status, unit
   - View transaction details, receipts, GL entries

3. **Replenishment History**:
   - All replenishments for a unit
   - Requested vs approved amounts
   - Status tracking

4. **Unit Details Page**:
   - Current balance and usage
   - Recent transactions and replenishments
   - Progress bar showing balance usage
   - Balance summary (Float, Used, Replenished, Available)

### Monitoring Features

- **Balance Alerts**: Visual indicators when balance is low
- **Progress Bar**: Shows how much of float has been used
- **Status Badges**: Color-coded status indicators
- **Real-time Updates**: Balance updates immediately after transactions

---

## Best Practices

### Setup Best Practices

1. **Float Amount**: Set appropriate float based on typical monthly expenses
2. **Approval Threshold**: Set threshold to balance control and efficiency
3. **Maximum Limit**: Set to prevent excessive balances
4. **Custodian Selection**: Choose responsible, trustworthy individuals
5. **Supervisor Assignment**: Assign supervisors with appropriate authority

### Operational Best Practices

1. **Daily Reconciliation**: Reconcile cash on hand with system balance daily
2. **Receipt Management**: Always attach receipts to transactions
3. **Timely Replenishment**: Request replenishment before balance is too low
4. **Proper Categorization**: Use correct expense accounts for proper reporting
5. **Documentation**: Maintain clear descriptions and notes

### Control Best Practices

1. **Segregation of Duties**: Custodian should not be supervisor
2. **Regular Audits**: Periodic review of transactions and balances
3. **Limit Enforcement**: Enforce maximum limits and approval thresholds
4. **Access Control**: Limit access to authorized personnel only
5. **Audit Trail**: Maintain complete audit trail of all transactions

### Accounting Best Practices

1. **Timely GL Posting**: Ensure all transactions are posted to GL promptly
2. **Account Reconciliation**: Regularly reconcile petty cash GL account
3. **Bank Reconciliation**: Include petty cash transactions in bank reconciliation
4. **Period-end Closing**: Ensure all transactions are posted before period close
5. **Documentation**: Maintain supporting documents for all transactions

---

## Workflow Diagrams

### Transaction Flow

```
[Create Transaction]
        ↓
[Amount ≤ Threshold?]
    ↙        ↘
  Yes        No
    ↓          ↓
[Auto-Approved]  [Submitted]
    ↓              ↓
[Post to GL]   [Await Approval]
    ↓              ↓
[Update Balance] [Approve]
                    ↓
                [Post to GL]
                    ↓
                [Update Balance]
```

### Replenishment Flow

```
[Request Replenishment]
        ↓
[Create Replenishment Record]
        ↓
[Status: Submitted]
        ↓
[Supervisor Approves]
        ↓
[Status: Approved]
        ↓
[Post to GL]
        ↓
[Create Journal Entry]
        ↓
[Update Balance]
```

### Setup Flow

```
[Create Petty Cash Unit]
        ↓
[Enter Unit Details]
        ↓
[Select Bank Account]
        ↓
[Save Unit]
        ↓
[Create Journal Entry]
    (Dr. Petty Cash, Cr. Bank)
        ↓
[Post to GL]
        ↓
[Unit Ready for Use]
```

---

## Technical Details

### Database Tables

1. **petty_cash_units**: Main unit records
2. **petty_cash_transactions**: Expense transactions
3. **petty_cash_transaction_items**: Line items for transactions
4. **petty_cash_replenishments**: Replenishment requests
5. **payments**: Payment records (for GL integration)
6. **payment_items**: Payment line items
7. **journals**: Journal entries (for setup and replenishments)
8. **journal_items**: Journal line items
9. **gl_transactions**: GL transaction records

### Key Relationships

- Petty Cash Unit → Transactions (One-to-Many)
- Petty Cash Unit → Replenishments (One-to-Many)
- Transaction → Payment (One-to-One)
- Payment → Payment Items (One-to-Many)
- Replenishment → Journal (One-to-One)
- Journal → Journal Items (One-to-Many)

### Status Values

**Transactions**:
- `draft`: Created but not submitted
- `submitted`: Awaiting approval
- `approved`: Approved, ready for GL posting
- `posted`: Posted to GL
- `rejected`: Rejected by supervisor

**Replenishments**:
- `draft`: Created but not submitted
- `submitted`: Awaiting approval
- `approved`: Approved, ready for GL posting
- `posted`: Posted to GL
- `rejected`: Rejected by supervisor

---

## Troubleshooting

### Common Issues

1. **Balance Not Updating**:
   - Check if transaction is approved
   - Verify GL posting was successful
   - Check for errors in logs

2. **GL Entries Not Created**:
   - Verify approval workflow is completed
   - Check journal approval settings
   - Review error logs

3. **Approval Button Not Showing**:
   - Verify user has approval permissions
   - Check transaction status
   - Ensure supervisor is assigned

4. **Replenishment Not Posting**:
   - Verify replenishment is approved
   - Check bank account has sufficient balance
   - Review journal creation logs

---

## Conclusion

The Petty Cash Management System provides a comprehensive solution for managing petty cash operations with proper accounting controls, automated GL posting, and complete audit trails. By following the workflows and best practices outlined in this document, organizations can maintain efficient petty cash operations while ensuring compliance and proper financial controls.

---

**Document Version**: 1.0  
**Last Updated**: December 2025  
**System Version**: Smart Accounting System


