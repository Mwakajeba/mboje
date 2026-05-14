# IAS 37 Provisions - Enhancement Implementation Status

## ✅ COMPLETED (Phase 1)

### 1. Database Schema Enhancements
- ✅ **Asset Linkage Fields** (`2025_12_17_200000_add_asset_linkage_fields_to_provisions.php`)
  - `related_asset_id` - Links to PPE asset for Environmental provisions
  - `asset_category` - Asset category classification
  - `is_capitalised` - Flag for capitalisation into PPE
  - `depreciation_start_date` - Reference for depreciation tracking
  - `undiscounted_amount` - Future undiscounted amount for disclosure
  - `discount_rate_id` - Link to central discount rate table
  - `computation_assumptions` - JSON field for storing calculation inputs

- ✅ **Central Discount Rate Governance** (`2025_12_17_200100_create_discount_rates_table.php`)
  - Discount rate master table with IFRS standard, usage context, currency, rate type
  - Effective date management
  - Approval workflow integration
  - Model: `App\Models\DiscountRate` with scopes for active rates

### 2. Enhanced Provision Templates Config
- ✅ **Field Visibility Rules** - Template-driven conditional field display
  - `discounting_fields` - Show/hide based on provision type
  - `asset_linkage_fields` - Show/hide for Environmental provisions
  - `probability_fields` - Show/hide based on type (mandatory for Legal, hidden for Warranty)
  - `computation_panel` - Enable/disable computation UI

- ✅ **Computation Logic Definitions**
  - Warranty: `Units × Defect % × Cost`
  - Onerous: `MIN(Cost to Fulfill, Penalty to Exit)`
  - Environmental: `PV = Future Cost / (1 + r)^n`
  - Restructuring: `Employees × Average Termination Cost`
  - Legal: Expected value or most likely outcome

- ✅ **Account Restrictions**
  - Template-defined allowed expense accounts
  - Template-defined allowed provision accounts
  - Template-defined unwinding accounts (if applicable)

### 3. Model Updates
- ✅ **Provision Model** - Added new fillable fields and casts
- ✅ **DiscountRate Model** - Complete with relationships and scopes

---

## 🚧 PENDING IMPLEMENTATION (Phase 2)

### 1. Computation Service Classes
**Priority: HIGH**

Create dedicated computation services for each provision type:

- `App\Services\ProvisionComputation\WarrantyComputationService.php`
  - Calculate: `Units Sold × Defect Rate % × Average Repair Cost`
  - Store assumptions in `computation_assumptions` JSON field

- `App\Services\ProvisionComputation\OnerousComputationService.php`
  - Calculate: `MIN(Cost to Fulfill, Penalty to Exit)`
  - Validate both amounts are provided

- `App\Services\ProvisionComputation\EnvironmentalComputationService.php`
  - Calculate Present Value: `PV = Future Cost / (1 + r)^n`
  - Handle asset linkage and capitalisation logic
  - Post **Dr Asset / Cr Provision** instead of expense

- `App\Services\ProvisionComputation\RestructuringComputationService.php`
  - Calculate: `Employees × Average Termination Cost`
  - **BLOCK** training, marketing, future operating losses
  - Validate only allowed cost types

- `App\Services\ProvisionComputation\LegalComputationService.php`
  - Expected value: `Σ (Probability × Outcome)`
  - Most likely outcome: Single best estimate

### 2. Template-Driven UI (Frontend)
**Priority: HIGH**

Update `resources/views/accounting/provisions/create.blade.php`:

- ✅ Show/hide discounting fields based on `field_visibility.discounting_fields`
- ✅ Show/hide asset linkage fields based on `field_visibility.asset_linkage_fields`
- ✅ Show/hide probability fields based on `field_visibility.probability_fields`
- ✅ Display computation panels based on `computation.enabled`
- ✅ Filter account dropdowns based on `account_restrictions`

**JavaScript Required:**
- Dynamic field visibility based on provision type selection
- Computation panel calculations (real-time)
- Account filtering based on template restrictions

### 3. Account Mapping Controls
**Priority: MEDIUM**

Update `ProvisionController@create`:

- Filter `expenseAccounts` based on template's `account_restrictions.expense_accounts`
- Filter `provisionAccounts` based on template's `account_restrictions.provision_accounts`
- Filter `financeCostAccounts` based on template's `account_restrictions.unwinding_accounts`

**Note:** This requires mapping account types/categories in `chart_accounts` table or creating a separate mapping table.

### 4. Environmental Provision Logic Enhancement
**Priority: HIGH**

Update `ProvisionService`:

- **Asset Linkage**: When `provision_type = 'environmental'` and `related_asset_id` is set:
  - Post **Dr Asset (PPE) / Cr Provision** instead of **Dr Expense / Cr Provision**
  - Set `is_capitalised = true`
  - Link to asset for depreciation tracking

- **PV Calculation**: When `is_discounted = true`:
  - Calculate `undiscounted_amount` from `original_estimate` and discount rate
  - Store both discounted and undiscounted amounts

### 5. Restructuring Validation
**Priority: MEDIUM**

Add validation in `ProvisionService` or dedicated validator:

- **Block** inclusion of:
  - Training costs
  - Marketing costs
  - Future operating losses

- **Allow** only:
  - Termination benefits
  - Contract termination penalties
  - Direct restructuring costs

**Implementation:** Add checkboxes/tags in UI and validate server-side.

### 6. Disclosure Engine
**Priority: MEDIUM**

Create `App\Services\ProvisionDisclosureService.php`:

- Auto-generate IAS 37 disclosure notes:
  - Opening balance
  - Additions (new provisions)
  - Utilisation
  - Reversals
  - Closing balance
  - Nature & timing
  - Uncertainties

**Output Formats:**
- PDF report
- Excel export
- JSON API endpoint

### 7. Periodic Review Automation
**Priority: LOW**

Create Artisan command: `php artisan provisions:periodic-review`

**Functionality:**
- Flag provisions requiring remeasurement
- Calculate default discount unwinding for discounted provisions
- Generate review checklist
- Send notifications to responsible users

**Schedule:** Monthly or quarterly via Laravel scheduler.

### 8. Discount Rate Auto-Population
**Priority: MEDIUM**

Update `ProvisionController@create` and `ProvisionService`:

- When `is_discounted = true`:
  - Auto-fetch active discount rate from `DiscountRate` table
  - Populate `discount_rate_id` and `discount_rate` fields
  - Make rate read-only (governance control)

---

## 📋 IMPLEMENTATION CHECKLIST

### Immediate Next Steps (Critical Path)

1. **Run Migrations**
   ```bash
   php artisan migrate
   ```

2. **Create Computation Services**
   - Start with Warranty and Environmental (most complex)

3. **Update Create/Edit Forms**
   - Add JavaScript for dynamic field visibility
   - Add computation panels
   - Add account filtering

4. **Enhance ProvisionService**
   - Integrate computation services
   - Add Environmental asset capitalisation logic
   - Add restructuring validation

5. **Test Each Provision Type**
   - Verify gatekeeper logic still works
   - Verify computation calculations
   - Verify GL postings are correct

### Secondary Tasks

6. Create discount rate management UI
7. Implement disclosure engine
8. Create periodic review command
9. Add stricter validation for restructuring

---

## 🔍 KEY DESIGN DECISIONS

### 1. One Provision Master Record - Multiple Templates
✅ **Implemented** - Single `provisions` table with template-driven behavior

### 2. Template Controls Field Visibility
✅ **Config Defined** - Needs UI implementation

### 3. Computation Panels vs Free Entry
✅ **Config Defined** - Needs service classes and UI

### 4. Account Mapping Restrictions
✅ **Config Defined** - Needs account type mapping and filtering

### 5. Central Discount Rate Governance
✅ **Table Created** - Needs management UI

### 6. Environmental Asset Capitalisation
✅ **Fields Added** - Needs logic in ProvisionService

---

## 📝 NOTES

- **Dynamic UI ≠ Dynamic Accounting Rules**: Gatekeeper logic always runs, hidden fields still validated server-side
- **Templates Cannot Bypass IAS 37**: All recognition criteria must still be met
- **Computation Assumptions**: Stored in JSON for audit trail and recalculation
- **Undiscounted Amount**: Required for disclosure even when provision is discounted

---

## 🚀 QUICK START

To continue implementation:

1. Run migrations: `php artisan migrate`
2. Start with computation services (Warranty is simplest)
3. Update create form with dynamic field visibility
4. Test each provision type end-to-end

---

**Last Updated:** 2025-12-17
**Status:** Phase 1 Complete (Database & Config), Phase 2 Pending (Services & UI)

