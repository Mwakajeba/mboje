# Petty Cash Management System - Complete Implementation Documentation

**Document Version**: 2.0  
**Last Updated**: December 2025  
**System**: Smart Accounting System

---

## Table of Contents

1. [Executive Summary](#executive-summary)
2. [System Overview](#system-overview)
3. [System Architecture](#system-architecture)
4. [Configuration & Setup](#configuration--setup)
5. [Core Functionalities](#core-functionalities)
6. [Operation Modes](#operation-modes)
7. [General Ledger Integration](#general-ledger-integration)
8. [Register & Reconciliation](#register--reconciliation)
9. [Approval Workflows](#approval-workflows)
10. [Reporting & Analytics](#reporting--analytics)
11. [Technical Implementation](#technical-implementation)
12. [Database Schema](#database-schema)
13. [API & Routes](#api--routes)
14. [User Guide](#user-guide)
15. [Best Practices](#best-practices)
16. [Troubleshooting](#troubleshooting)

---

## Executive Summary

The Petty Cash Management System is a comprehensive solution designed to manage small, day-to-day cash expenses efficiently while maintaining proper accounting controls, automated General Ledger (GL) posting, and complete audit trails. The system supports multiple petty cash units, dual operation modes (Sub-Imprest and Standalone), automated replenishment workflows, real-time balance tracking, and comprehensive reporting capabilities.

### Key Achievements

- ✅ **Dual Operation Modes**: Sub-Imprest Mode and Standalone Mode
- ✅ **Automated GL Integration**: Automatic double-entry accounting for all transactions
- ✅ **Petty Cash Register**: Complete ledger of all petty cash movements
- ✅ **Reconciliation System**: Cash count reconciliation with variance tracking
- ✅ **Approval Workflows**: Configurable approval thresholds and multi-level approvals
- ✅ **Sub-Imprest Integration**: Seamless integration with Imprest Management System
- ✅ **Comprehensive Reporting**: Register reports, reconciliation reports, and export capabilities
- ✅ **Bank Account Integration**: Direct integration with bank accounts for float setup and replenishments

---

## System Overview

### Purpose

The Petty Cash Management System enables organizations to:
- Manage multiple petty cash units across branches or departments
- Track all petty cash transactions with complete audit trails
- Automate General Ledger postings for accounting accuracy
- Maintain proper cash controls through approval workflows
- Reconcile physical cash with system balances
- Generate comprehensive reports for management and audit purposes

### Key Features

1. **Multiple Petty Cash Units**: Support for different branches or departments
2. **Automated GL Posting**: Automatic double-entry accounting for all transactions
3. **Approval Workflows**: Configurable approval thresholds and supervisor approvals
4. **Replenishment Management**: Automated replenishment requests and processing
5. **Real-time Balance Tracking**: Current balance monitoring with limits and alerts
6. **Petty Cash Register**: Complete ledger of all movements
7. **Reconciliation System**: Cash count reconciliation with variance calculation
8. **Dual Operation Modes**: Sub-Imprest Mode and Standalone Mode
9. **Comprehensive Reporting**: Transaction history, balance reports, and audit trails
10. **Export Capabilities**: PDF and Excel export for all reports

---

## System Architecture

### Components

1. **Petty Cash Units**: Core entities representing individual petty cash funds
2. **Petty Cash Transactions**: Expense transactions from petty cash
3. **Petty Cash Replenishments**: Requests and processing of fund replenishments
4. **Petty Cash Register**: Complete ledger of all petty cash movements
5. **Petty Cash Settings**: System-wide configuration for operation modes and rules
6. **Expense Categories**: Categorization of petty cash expenses
7. **Integration Layer**: GL, Payment, Journal, and Imprest integrations

### Technology Stack

- **Framework**: Laravel 12.x
- **Database**: MySQL
- **Frontend**: Bootstrap 5, DataTables, jQuery, SweetAlert2
- **PDF Generation**: DomPDF (barryvdh/laravel-dompdf)
- **Excel Export**: Maatwebsite Excel (maatwebsite/excel)
- **Authentication**: Laravel Auth

---

## Configuration & Setup

### 1. System Settings Configuration

**Location**: Settings → Petty Cash Settings (`/settings/petty-cash`)

**Configuration Options**:

1. **Operation Mode**:
   - **Sub-Imprest Mode**: Petty cash transactions are linked to imprest requests
   - **Standalone Mode**: Petty cash operates independently

2. **Default Float Amount**: Default initial float amount for new units

3. **Maximum Transaction Amount**: Maximum allowed transaction amount

4. **Allowed Expense Categories**: GL accounts that can be used for petty cash expenses

5. **Receipt Requirements**: Whether receipts are mandatory for transactions

6. **Minimum Balance Trigger**: Balance threshold that triggers replenishment alerts

7. **Auto-Approve Below Threshold**: Automatically approve transactions below a certain amount

8. **Notes**: Additional configuration notes

**Implementation**:
- Model: `App\Models\PettyCash\PettyCashSettings`
- Table: `petty_cash_settings`
- Controller: `App\Http\Controllers\SettingsController`
- View: `resources/views/settings/petty-cash.blade.php`

### 2. Create Petty Cash Unit

**Location**: Accounting Management → Petty Cash Units → Create New Unit

**Required Information**:

- **Unit Name**: Descriptive name (e.g., "HQ Petty Cash", "Branch A Petty Cash")
- **Unit Code**: Unique identifier (e.g., "PC-HQ", "PC-BR-A")
- **Branch**: Select branch if unit is branch-specific (optional)
- **Custodian**: Person responsible for managing the petty cash
- **Supervisor**: Person who approves replenishments and large expenses
- **Float Amount**: Initial cash amount for the unit (pre-filled from settings)
- **Maximum Limit**: Maximum allowed balance (optional)
- **Approval Threshold**: Amount above which supervisor approval is required
- **Petty Cash Account**: GL account for petty cash asset
- **Bank Account**: Bank account from which the float will be debited (replaces suspense account)

**What Happens Behind the Scenes**:

1. Petty cash unit record is created
2. Current balance is set equal to float amount
3. **Automatic GL Double Entry**:
   - **Debit**: Petty Cash Account (increases petty cash asset)
   - **Credit**: Bank Account (decreases bank balance)
4. Journal entry is created with reference type "Petty Cash Unit Setup"
5. Opening balance entry is created in `petty_cash_register` table
6. Journal goes through approval workflow (creates GL transactions if auto-approved)

**Accounting Impact**:
```
Dr. Petty Cash Account          XXX
    Cr. Bank Account                    XXX
```

**Implementation**:
- Controller: `App\Http\Controllers\Accounting\PettyCash\PettyCashUnitController`
- Model: `App\Models\PettyCash\PettyCashUnit`
- View: `resources/views/accounting/petty-cash/units/create.blade.php`

---

## Core Functionalities

### 1. Create Petty Cash Transaction

**Purpose**: Record daily expenses from petty cash.

**Location**: Petty Cash Unit → Show → New Transaction (or Transactions menu)

**Steps**:

1. Navigate to transaction creation page
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

1. Transaction record is created
2. Transaction items are created
3. If auto-approved:
   - **Payment** record is created (reference_type = 'petty_cash')
   - **Payment Items** are created for each expense line
   - **GL Transactions** are created:
     - **Debit**: Expense Account(s) (one for each line item)
     - **Credit**: Petty Cash Account
   - **Register Entry** is created in `petty_cash_register`
   - Current balance is reduced by transaction amount
   - Balance after is calculated and stored
   - If in Sub-Imprest mode, an Imprest Request is automatically created
4. If requires approval:
   - Status set to "submitted"
   - Balance remains unchanged until approved

**Accounting Impact (Auto-Approved)**:
```
Dr. Expense Account(s)          XXX
    Cr. Petty Cash Account              XXX
```

**Implementation**:
- Controller: `App\Http\Controllers\Accounting\PettyCash\PettyCashTransactionController`
- Model: `App\Models\PettyCash\PettyCashTransaction`
- Service: `App\Services\PettyCashService`

### 2. Approve Transaction

**Purpose**: Approve transactions that exceed the approval threshold.

**Steps**:

1. Navigate to transaction list or unit details page
2. Click **Approve** button on submitted transactions
3. Confirm approval (SweetAlert confirmation)

**What Happens Behind the Scenes**:

1. Transaction status changes to "approved"
2. **Payment** record is created (if not already created)
3. **Payment Items** are created for each expense line
4. **GL Transactions** are created:
   - **Debit**: Expense Account(s)
   - **Credit**: Petty Cash Account
5. **Register Entry** is created in `petty_cash_register`
6. Current balance is reduced
7. Balance after is updated
8. If in Sub-Imprest mode, an Imprest Request is automatically created

### 3. Request Replenishment

**Purpose**: Request additional funds when petty cash balance is low.

**Location**: Petty Cash Unit → Show → Request Replenishment

**Steps**:

1. Navigate to replenishment creation page
2. Fill in replenishment details:
   - **Request Date**: Date of request
   - **Requested Amount**: Amount needed to restore float
   - **Source Bank Account**: Bank account from which funds will be transferred
   - **Reason**: Reason for replenishment
   - **Notes**: Additional notes (optional)

**What Happens Behind the Scenes**:

1. Replenishment record is created with status "draft" or "submitted"
2. If approval is required, status is set to "submitted"
3. Replenishment number is auto-generated (format: PCR-YYYYMM-####)

**Implementation**:
- Controller: `App\Http\Controllers\Accounting\PettyCash\PettyCashReplenishmentController`
- Model: `App\Models\PettyCash\PettyCashReplenishment`

### 4. Approve Replenishment

**Purpose**: Approve replenishment requests.

**Steps**:

1. Navigate to replenishment list or unit details page
2. Click **Approve** button on submitted replenishments
3. Confirm approval

**What Happens Behind the Scenes**:

1. Replenishment status changes to "approved"
2. Approved amount is set (may differ from requested amount)
3. Approved by and approved date are recorded

### 5. Post Replenishment to GL

**Purpose**: Post approved replenishment to GL, transferring funds from bank to petty cash.

**Steps**:

1. Navigate to replenishment details
2. Click **Post to GL** button
3. Confirm posting

**What Happens Behind the Scenes**:

1. **Journal** entry is created with reference type "Petty Cash Replenishment"
2. **Journal Items** are created:
   - **Debit**: Petty Cash Account (increases petty cash)
   - **Credit**: Bank Account (decreases bank balance)
3. **Register Entry** is created in `petty_cash_register`
4. Journal goes through approval workflow
5. Current balance is increased by approved amount
6. Balance after is updated

**Accounting Impact**:
```
Dr. Petty Cash Account          XXX
    Cr. Bank Account                    XXX
```

---

## Operation Modes

### Sub-Imprest Mode

**Description**: Petty cash transactions are integrated with the Imprest Management System. When a transaction is approved/posted, an imprest request is automatically created and linked to the transaction.

**Features**:

1. **Automatic Imprest Request Creation**:
   - When a petty cash transaction is approved/posted
   - Creates imprest request with proper items
   - Links register entry to imprest request
   - Handles multi-level approval if required

2. **Imprest Request Display**:
   - Transaction show page displays linked imprest request
   - Shows request number, status, purpose
   - Link to view full imprest request details

3. **Retirement Workflow**:
   - Retirement handled through imprest module
   - Register entries linked to imprest requests

**Implementation**:
- Service: `App\Services\PettyCashImprestService`
- Service: `App\Services\PettyCashModeService`
- Integration in: `PettyCashTransactionController::approve()`

### Standalone Mode

**Description**: Petty cash operates independently without integration with the Imprest Management System.

**Features**:

1. **Independent Operation**:
   - Transactions are processed without imprest requests
   - Direct GL posting
   - Standard replenishment workflow

2. **Direct Replenishment**:
   - Replenishments are processed directly
   - No imprest integration required

**Implementation**:
- Mode detection: `PettyCashModeService::isStandaloneMode()`
- Conditional logic in transaction approval

---

## General Ledger Integration

### Automatic GL Posting

The system automatically creates GL entries for:

1. **Petty Cash Unit Setup**:
   - Creates Journal entry when unit is created
   - Debits Petty Cash Account, Credits Bank Account
   - Reference Type: "Petty Cash Unit Setup"

2. **Petty Cash Transactions**:
   - Creates Payment and PaymentItems records
   - Creates GL Transactions:
     - Debits Expense Account(s)
     - Credits Petty Cash Account
   - Reference Type: "Petty Cash Transaction"
   - Posts immediately if auto-approved, or after approval

3. **Petty Cash Replenishments**:
   - Creates Journal entry when replenishment is posted
   - Debits Petty Cash Account, Credits Bank Account
   - Reference Type: "Petty Cash Replenishment"

### GL Transaction Details

All GL transactions include:
- **Transaction Type**: 'petty_cash' or 'journal'
- **Reference Type**: 'Petty Cash Transaction', 'Petty Cash Replenishment', or 'Petty Cash Unit Setup'
- **Reference ID**: Links to transaction, replenishment, or unit
- **Date**: Transaction date
- **Description**: Detailed description
- **Branch ID**: Branch associated with the transaction
- **User ID**: User who created the transaction

### Implementation

- Service: `App\Services\PettyCashService`
- Methods:
  - `postTransactionToGL(PettyCashTransaction $transaction)`
  - `postReplenishmentToGL(PettyCashReplenishment $replenishment)`

---

## Register & Reconciliation

### Petty Cash Register

**Purpose**: Complete ledger of all petty cash movements.

**Location**: Petty Cash Unit → Show → Register Button (`/accounting/petty-cash/register/{unit_id}`)

**Features**:

1. **DataTables Display**:
   - Server-side processing for performance
   - Real-time balance tracking
   - Comprehensive filtering

2. **Filters**:
   - Date Range (From/To)
   - Status (Pending, Approved, Posted, Rejected)
   - Entry Type (Opening Balance, Disbursement, Replenishment, Adjustment)

3. **Summary Cards**:
   - Opening Balance
   - Total Disbursed
   - Total Replenished
   - Closing Cash (Calculated)

4. **Export Capabilities**:
   - Export to PDF
   - Export to Excel

5. **Register Entry Details**:
   - PCV Number
   - Date
   - Entry Type
   - Description
   - Amount (with nature: debit/credit)
   - GL Account
   - Requested By
   - Approved By
   - Status
   - Balance After

**Implementation**:
- Controller: `App\Http\Controllers\Accounting\PettyCash\PettyCashRegisterController`
- Model: `App\Models\PettyCash\PettyCashRegister`
- View: `resources/views/accounting/petty-cash/register/index.blade.php`
- Service: `App\Services\PettyCashModeService::getRegisterEntries()`

### Reconciliation System

**Purpose**: Compare physical cash count with system's calculated balance.

**Location**: Petty Cash Unit → Show → Reconciliation Button (`/accounting/petty-cash/register/{unit_id}/reconciliation`)

**Features**:

1. **As of Date Selector**: Select date for reconciliation

2. **Cash Count Input**: Enter physical cash count

3. **Automatic Variance Calculation**:
   - System Balance (from register)
   - Calculated Balance (Opening + Replenishments - Disbursements)
   - Physical Cash Count
   - Variance (Physical - Calculated)

4. **Summary Cards**:
   - Opening Balance
   - Total Disbursed
   - Total Replenished
   - Closing Cash (Calculated)
   - System Balance
   - Variance

5. **Outstanding Vouchers Table**:
   - Displays pending receipts
   - Shows transaction details
   - Helps identify discrepancies

6. **Reconciliation Notes**: Save notes for reconciliation

7. **Save Functionality**: Save reconciliation results

**Implementation**:
- Controller: `App\Http\Controllers\Accounting\PettyCash\PettyCashRegisterController::reconciliation()`
- View: `resources/views/accounting/petty-cash/register/reconciliation.blade.php`
- Service: `App\Services\PettyCashModeService::getReconciliationSummary()`

---

## Approval Workflows

### Transaction Approval

**Automatic Approval**:
- Transactions ≤ Approval Threshold are auto-approved
- GL posting happens immediately
- Balance is updated immediately
- Register entry is created

**Manual Approval**:
- Transactions > Approval Threshold require supervisor approval
- Supervisor can approve from transaction list or details page
- After approval, GL posting occurs automatically
- Balance is updated
- Register entry is created
- If in Sub-Imprest mode, Imprest Request is created

### Replenishment Approval

**Workflow**:

1. Replenishment is created (status: draft or submitted)
2. Supervisor reviews and approves
3. Approved amount may differ from requested amount
4. After approval, replenishment can be posted to GL
5. GL posting transfers funds from bank to petty cash
6. Register entry is created

### Approval Settings

- **Approval Threshold**: Set per unit, determines when approval is required
- **Supervisor**: Assigned per unit, approves transactions and replenishments
- **Multi-level Approval**: Can be configured in system settings (future enhancement)

---

## Reporting & Analytics

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

4. **Petty Cash Register Report**:
   - Complete ledger of all movements
   - Filters: Date range, Status, Entry Type
   - Summary statistics
   - Export to PDF/Excel

5. **Reconciliation Report**:
   - Cash count reconciliation
   - Variance analysis
   - Outstanding vouchers

6. **Unit Details Page**:
   - Current balance and usage
   - Recent transactions and replenishments
   - Progress bar showing balance usage
   - Balance summary (Float, Used, Replenished, Available)

### Export Capabilities

- **PDF Export**: Using DomPDF
- **Excel Export**: Using Maatwebsite Excel
- **Formatted Reports**: Professional formatting with headers, footers, and styling

---

## Technical Implementation

### Models

1. **PettyCashUnit** (`App\Models\PettyCash\PettyCashUnit`):
   - Main unit entity
   - Relationships: branch, custodian, supervisor, pettyCashAccount, bankAccount, transactions, replenishments, register

2. **PettyCashTransaction** (`App\Models\PettyCash\PettyCashTransaction`):
   - Expense transaction entity
   - Relationships: pettyCashUnit, items, expenseCategory, createdBy, approvedBy, payment

3. **PettyCashReplenishment** (`App\Models\PettyCash\PettyCashReplenishment`):
   - Replenishment request entity
   - Relationships: pettyCashUnit, requestedBy, approvedBy, journal

4. **PettyCashRegister** (`App\Models\PettyCash\PettyCashRegister`):
   - Register entry entity
   - Relationships: pettyCashUnit, transaction, replenishment, imprestRequest, glAccount, requestedBy, approvedBy

5. **PettyCashSettings** (`App\Models\PettyCash\PettyCashSettings`):
   - System settings entity
   - Methods: `getForCompany()`, `isSubImprestMode()`, `isStandaloneMode()`

6. **PettyCashExpenseCategory** (`App\Models\PettyCash\PettyCashExpenseCategory`):
   - Expense category entity

### Controllers

1. **PettyCashUnitController**:
   - `index()`: List all units
   - `create()`: Show creation form
   - `store()`: Create new unit with GL entries
   - `show()`: Display unit details
   - `edit()`: Show edit form
   - `update()`: Update unit
   - `destroy()`: Delete unit

2. **PettyCashTransactionController**:
   - `index()`: List all transactions
   - `create()`: Show creation form
   - `store()`: Create new transaction
   - `show()`: Display transaction details
   - `approve()`: Approve transaction (with Sub-Imprest integration)
   - `postToGL()`: Manually post to GL

3. **PettyCashReplenishmentController**:
   - `index()`: List all replenishments
   - `create()`: Show creation form
   - `store()`: Create new replenishment
   - `show()`: Display replenishment details
   - `approve()`: Approve replenishment
   - `postToGL()`: Post replenishment to GL

4. **PettyCashRegisterController**:
   - `index()`: Display register with DataTables
   - `getRegisterData()`: AJAX endpoint for DataTables
   - `reconciliation()`: Display reconciliation view
   - `saveReconciliation()`: Save reconciliation results
   - `exportPdf()`: Export register to PDF
   - `exportExcel()`: Export register to Excel

5. **SettingsController**:
   - `pettyCashSettings()`: Display settings page
   - `updatePettyCashSettings()`: Update settings

### Services

1. **PettyCashService** (`App\Services\PettyCashService`):
   - `postTransactionToGL()`: Post transaction to GL
   - `postReplenishmentToGL()`: Post replenishment to GL

2. **PettyCashModeService** (`App\Services\PettyCashModeService`):
   - `getSettings()`: Get settings for company
   - `isSubImprestMode()`: Check if Sub-Imprest mode
   - `isStandaloneMode()`: Check if Standalone mode
   - `createRegisterEntry()`: Create register entry for transaction
   - `createReplenishmentRegisterEntry()`: Create register entry for replenishment
   - `createOpeningBalanceEntry()`: Create opening balance entry
   - `linkToImprest()`: Link transaction to imprest request
   - `getRegisterEntries()`: Get register entries with filters
   - `getReconciliationSummary()`: Calculate reconciliation summary

3. **PettyCashImprestService** (`App\Services\PettyCashImprestService`):
   - `createImprestRequestFromPettyCashTransaction()`: Create imprest request from transaction

---

## Database Schema

### Tables

1. **petty_cash_units**:
   - `id`, `company_id`, `branch_id`
   - `name`, `code`
   - `custodian_id`, `supervisor_id`
   - `float_amount`, `current_balance`, `maximum_limit`, `approval_threshold`
   - `petty_cash_account_id`, `bank_account_id`
   - `is_active`, `notes`
   - `created_at`, `updated_at`

2. **petty_cash_transactions**:
   - `id`, `petty_cash_unit_id`
   - `transaction_number`, `transaction_date`
   - `description`, `payee_type`, `payee`, `customer_id`, `supplier_id`
   - `amount`, `status`
   - `expense_category_id`
   - `receipt_path`, `notes`
   - `created_by`, `approved_by`, `approved_at`
   - `balance_before`, `balance_after`
   - `payment_id`, `journal_id`
   - `created_at`, `updated_at`

3. **petty_cash_transaction_items**:
   - `id`, `petty_cash_transaction_id`
   - `gl_account_id`, `description`, `amount`
   - `created_at`, `updated_at`

4. **petty_cash_replenishments**:
   - `id`, `petty_cash_unit_id`
   - `replenishment_number`, `request_date`
   - `requested_amount`, `approved_amount`
   - `source_bank_account_id`
   - `reason`, `status`, `notes`
   - `requested_by`, `approved_by`, `approved_at`
   - `journal_id`
   - `created_at`, `updated_at`

5. **petty_cash_register**:
   - `id`, `petty_cash_unit_id`
   - `petty_cash_transaction_id`, `petty_cash_replenishment_id`, `imprest_request_id`
   - `register_date`, `pcv_number`
   - `description`, `amount`, `entry_type`, `nature`
   - `gl_account_id`
   - `requested_by`, `approved_by`
   - `status`, `balance_after`, `notes`
   - `created_at`, `updated_at`

6. **petty_cash_settings**:
   - `id`, `company_id`
   - `operation_mode` (sub_imprest/standalone)
   - `default_float_amount`, `max_transaction_amount`
   - `allowed_expense_categories` (JSON)
   - `require_receipt`, `minimum_balance_trigger`
   - `auto_approve_below_threshold`, `notes`
   - `created_at`, `updated_at`

7. **petty_cash_expense_categories**:
   - `id`, `company_id`
   - `name`, `description`, `gl_account_id`
   - `is_active`
   - `created_at`, `updated_at`

### Relationships

- Petty Cash Unit → Transactions (One-to-Many)
- Petty Cash Unit → Replenishments (One-to-Many)
- Petty Cash Unit → Register Entries (One-to-Many)
- Transaction → Payment (One-to-One)
- Transaction → Transaction Items (One-to-Many)
- Transaction → Register Entry (One-to-One)
- Replenishment → Journal (One-to-One)
- Replenishment → Register Entry (One-to-One)
- Register Entry → Imprest Request (Many-to-One)

---

## API & Routes

### Route Groups

**Base Path**: `/accounting/petty-cash`

### Unit Routes

- `GET /units` - List all units
- `GET /units/create` - Show creation form
- `POST /units` - Create new unit
- `GET /units/{encodedId}` - Show unit details
- `GET /units/{encodedId}/edit` - Show edit form
- `PUT /units/{encodedId}` - Update unit
- `DELETE /units/{encodedId}` - Delete unit
- `GET /units/{encodedId}/transactions` - Get unit transactions (AJAX)
- `GET /units/{encodedId}/replenishments` - Get unit replenishments (AJAX)

### Transaction Routes

- `GET /transactions` - List all transactions
- `GET /transactions/create` - Show creation form
- `POST /transactions` - Create new transaction
- `GET /transactions/{encodedId}` - Show transaction details
- `GET /transactions/{encodedId}/edit` - Show edit form
- `PUT /transactions/{encodedId}` - Update transaction
- `DELETE /transactions/{encodedId}` - Delete transaction
- `POST /transactions/{encodedId}/approve` - Approve transaction
- `POST /transactions/{encodedId}/post-to-gl` - Post to GL

### Replenishment Routes

- `GET /replenishments` - List all replenishments
- `GET /replenishments/create` - Show creation form
- `POST /replenishments` - Create new replenishment
- `GET /replenishments/{encodedId}` - Show replenishment details
- `GET /replenishments/{encodedId}/edit` - Show edit form
- `PUT /replenishments/{encodedId}` - Update replenishment
- `POST /replenishments/{encodedId}/approve` - Approve replenishment
- `POST /replenishments/{encodedId}/post-to-gl` - Post to GL
- `DELETE /replenishments/{encodedId}` - Delete replenishment

### Register Routes

- `GET /register/{encodedId}` - Display register
- `GET /register/{encodedId}/reconciliation` - Display reconciliation
- `POST /register/{encodedId}/reconciliation` - Save reconciliation
- `GET /register/{encodedId}/export-pdf` - Export register to PDF
- `GET /register/{encodedId}/export-excel` - Export register to Excel

### Settings Routes

- `GET /settings/petty-cash` - Display settings
- `POST /settings/petty-cash` - Update settings

---

## User Guide

### For Custodians

1. **Creating Transactions**:
   - Navigate to your petty cash unit
   - Click "New Transaction"
   - Fill in transaction details
   - Add expense line items
   - Upload receipt (if required)
   - Submit transaction

2. **Viewing Register**:
   - Navigate to your petty cash unit
   - Click "Register" button
   - View all transactions and replenishments
   - Filter by date range, status, or entry type
   - Export to PDF or Excel

3. **Reconciliation**:
   - Navigate to your petty cash unit
   - Click "Reconciliation" button
   - Select "As of Date"
   - Enter physical cash count
   - Review variance
   - Save reconciliation

4. **Requesting Replenishment**:
   - Navigate to your petty cash unit
   - Click "Request Replenishment"
   - Enter requested amount and reason
   - Submit request

### For Supervisors

1. **Approving Transactions**:
   - Navigate to Transactions list or Unit details
   - Review submitted transactions
   - Click "Approve" button
   - Confirm approval

2. **Approving Replenishments**:
   - Navigate to Replenishments list or Unit details
   - Review submitted replenishments
   - Click "Approve" button
   - Set approved amount (may differ from requested)
   - Confirm approval

3. **Posting Replenishments to GL**:
   - Navigate to approved replenishment
   - Click "Post to GL" button
   - Confirm posting

### For Administrators

1. **Configuring System Settings**:
   - Navigate to Settings → Petty Cash Settings
   - Configure operation mode
   - Set default values
   - Configure approval rules
   - Save settings

2. **Creating Petty Cash Units**:
   - Navigate to Petty Cash Units → Create
   - Fill in unit details
   - Select bank account
   - Set float amount
   - Save unit (GL entries created automatically)

---

## Best Practices

### Setup Best Practices

1. **Float Amount**: Set appropriate float based on typical monthly expenses
2. **Approval Threshold**: Set threshold to balance control and efficiency
3. **Maximum Limit**: Set to prevent excessive balances
4. **Custodian Selection**: Choose responsible, trustworthy individuals
5. **Supervisor Assignment**: Assign supervisors with appropriate authority
6. **Bank Account Selection**: Use appropriate bank account for each unit

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

## Troubleshooting

### Common Issues

1. **Balance Not Updating**:
   - Check if transaction is approved
   - Verify GL posting was successful
   - Check for errors in logs
   - Verify register entry was created

2. **GL Entries Not Created**:
   - Verify approval workflow is completed
   - Check journal approval settings
   - Review error logs
   - Verify bank account has chart account assigned

3. **Approval Button Not Showing**:
   - Verify user has approval permissions
   - Check transaction status
   - Ensure supervisor is assigned

4. **Replenishment Not Posting**:
   - Verify replenishment is approved
   - Check bank account has sufficient balance
   - Review journal creation logs
   - Verify bank account has chart account assigned

5. **Register Entries Missing**:
   - Check if register entry creation is enabled
   - Verify transaction/replenishment is posted
   - Review service logs

6. **Sub-Imprest Integration Not Working**:
   - Verify operation mode is set to "Sub-Imprest"
   - Check if imprest module is enabled
   - Review imprest service logs

### Error Messages

- **"Bank account does not have a chart account assigned"**: Assign a chart account to the bank account
- **"Branch ID is required"**: Ensure petty cash unit has a branch assigned
- **"Insufficient balance"**: Check petty cash unit balance before transaction
- **"Approval threshold exceeded"**: Transaction requires supervisor approval

---

## Conclusion

The Petty Cash Management System provides a comprehensive solution for managing petty cash operations with proper accounting controls, automated GL posting, and complete audit trails. The system supports dual operation modes (Sub-Imprest and Standalone), comprehensive register tracking, reconciliation capabilities, and extensive reporting features.

By following the workflows and best practices outlined in this document, organizations can maintain efficient petty cash operations while ensuring compliance and proper financial controls.

---

**Document Version**: 2.0  
**Last Updated**: December 2025  
**System Version**: Smart Accounting System  
**Prepared By**: Development Team

