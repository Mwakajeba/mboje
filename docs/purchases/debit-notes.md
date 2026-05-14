# Debit Notes (Purchases) - README

## Overview
Debit Notes reverse/adjust purchases (returns, discounts, corrections). Lifecycle: Draft → Approved → Applied/Cancelled.

## Permissions
- view debit notes, create debit notes, edit debit notes, delete debit notes
- approve debit notes, apply debit notes, cancel debit notes

## Key Settings (SystemSetting keys)
- auto_approve_debit_notes: '0' or '1'
- inventory_default_purchase_payable_account: AP account id (e.g., 30)
- inventory_default_inventory_account: Inventory account id (e.g., 185)
- inventory_default_vat_account: VAT account id (e.g., 36)
- inventory_default_discount_income_account: Discount income id (optional; 0 to skip)

## Models
- App\Models\Purchase\DebitNote
- App\Models\Purchase\DebitNoteItem
- App\Models\Purchase\DebitNoteApplication

## Routes (purchases)
- GET purchases/debit-notes (index)
- GET purchases/debit-notes/create (create)
- POST purchases/debit-notes (store)
- GET purchases/debit-notes/{id} (show)
- GET purchases/debit-notes/{id}/edit (edit)
- PUT purchases/debit-notes/{id} (update)
- DELETE purchases/debit-notes/{id} (destroy)
- POST purchases/debit-notes/{id}/approve (approve)
- POST purchases/debit-notes/{id}/apply (apply)
- GET purchases/debit-notes/invoice-items/{invoice} (prefill supplier + items)

## Create
1) Go to Purchases → Debit Notes → Create
2) Select supplier (auto-fills if reference invoice chosen)
3) Optional: choose Reference Purchase Invoice → items auto-fill
4) Add/adjust items: quantity, unit_cost, VAT, return_to_stock
5) Save (Draft). If auto_approve_debit_notes=1, it approves immediately

### Header Fields (Create/Edit)
- Supplier: source supplier. Auto-selected when a reference invoice is chosen.
- Debit Note Date: accounting/stock movement date.
- Type: the business intent of the debit note (controls GL behavior hints):
  - return: goods returned to supplier. Inventory is reduced; AP is debited.
  - discount: post-invoice price reduction. Credits discount account.
  - correction: fix pricing/quantity errors.
  - overbilling: supplier overcharged; similar to correction.
  - service_adjustment: non-stock service adjustments; typically credit expense.
  - post_invoice_discount: discount given after invoice posting.
  - refund: supplier to return money (apply as refund).
  - restocking_fee: fee charged for returns; credit fee income.
  - scrap_writeoff: goods scrapped; no stock returns; credit expense/write-off.
  - advance_refund: refund of advance payment.
  - fx_adjustment: foreign exchange adjustment.
  - other: any other reason.
- Reason Code: structured code for analytics/workflow (e.g., wrong_item, defective_goods, overcharged, late_delivery). Used for reporting and validation rules.
- Reason: free-text description of the business reason. Appears on the document and GL memo.
- Warehouse: required when returning items to stock or tracking stock context.
- Refund now (checkbox):
  - When checked and later “Applied” as refund, GL debits cash/bank and credits AP.
  - When unchecked, you can apply to an invoice or keep as debit balance.
- Return items to stock (checkbox):
  - When checked, lines marked as return_to_stock will reduce inventory (movement_type=adjustment_out) on approval.
  - If unchecked, no stock movement; lines are treated as non-stock adjustments.
- Currency/Exchange Rate: present for multi-currency; exchange rate impacts reporting and optional FX adjustments.
- Discount Amount (header): an overall discount applied to the debit note total (credits discount income if configured).
- Notes / Terms & Conditions: free text.

### Item-Level Fields
- Item Name / Code / Unit of Measure: item metadata; if inventory_item_id is set, many are auto-derived.
- Inventory Item: optional link to inventory item. Required for stock returns.
- Quantity: number of units to credit.
- Unit Cost: cost basis for the credit; used for GL and inventory valuation.
- VAT Type: inclusive, exclusive, no_vat. Impacts line net and VAT amounts.
- VAT Rate: percentage (e.g., 18.00).
- VAT Amount: calculated from type and rate.
- Discount (line-level): type (none, percentage, fixed) and rate/amount.
- Return to Stock (line): when true and inventory_item_id present, inventory movement is posted on approval.
- Return Condition: optional (resellable, damaged, scrap, refurbish) for reporting.
- Notes: line memo.

### Examples
- Return: 2 units of item X at TZS 35,000 inclusive VAT → AP debit, Inventory credit (net), VAT input credit.
- Discount: overall TZS 10,000 discount on services → AP debit, Discount income credit.
- Service adjustment (no items): AP debit, Expense credit.

## Approve (GL + Inventory)
On approval:
- GL
  - Debit: Accounts Payable (total amount)
  - Credit: Inventory (subtotal) when returning inventory lines; otherwise credit purchase expense fallback
  - Credit: VAT Input (vat_amount)
  - Credit: Purchase Discount (discount_amount), if configured
- Inventory movements (per returned stock line)
  - movement_type: adjustment_out, quantity negative, unit_cost from line
  - Stock decreases, valuation updated

Status can be approved from Draft/Issued.

## Apply
Purpose: settle debit note amount.
- application_type = invoice: allocation only (no GL). Reduces invoice outstanding and debit note remaining
- application_type = refund: GL posted (Debit Bank/Cash, Credit AP)
- application_type = debit_balance: hold as supplier credit (no GL)

UI button on show sends POST to apply full remaining to reference invoice if present.

### Apply Fields (when using an application form)
- Application Type: invoice | refund | debit_balance
- Amount Applied: portion of remaining amount to apply
- Purchase Invoice: target invoice (for type=invoice)
- Bank Account: cash/bank account (for type=refund)
- Application Date: posting date
- Description / Notes: memo fields

## Cancel
Allowed in Draft/Issued/Approved. Status → cancelled. GL/Inventory reversal per business rules.

## Delete
Allowed in Draft. Cascades items/applications; remove/reverse associated GL per policy.

## Reverse Apply (Unapply)
Delete the specific row in debit_note_applications and:
- debit_note.applied_amount -= amount_applied
- debit_note.remaining_amount += amount_applied
- if remaining_amount > 0 and status == 'applied' → set status to 'approved'
- If application_type == 'refund', reverse (or delete) the application GL

## Validation & Rules
- Approve requires at least one item or a non-zero total.
- For stock returns: each line must have inventory_item_id; warehouse context required.
- Apply amount cannot exceed remaining_amount.
- Only Draft/Issued can be approved; Applied can be unapplied back to Approved.


## Files to Check
- Controller: app/Http/Controllers/Purchase/DebitNoteController.php
- Service: app/Services/DebitNoteService.php
- Models: app/Models/Purchase/*
- Views: resources/views/purchases/debit-notes/*
- Routes: routes/web.php
- Settings: app/Models/SystemSetting.php

## Common Issues
- Approve button not visible: missing permission or status not allowed
- Auto-approval missing: add auto_approve_debit_notes
- GL FK errors: configure SystemSetting accounts to valid chart_accounts.id
- Apply must be POST; show uses AJAX POST


