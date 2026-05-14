# IFRS 5 Held for Sale (HFS) Module - Developer Documentation

## Table of Contents
1. [Architecture Overview](#architecture-overview)
2. [Database Schema](#database-schema)
3. [Service Layer](#service-layer)
4. [API Endpoints](#api-endpoints)
5. [Business Logic Flow](#business-logic-flow)
6. [Integration Points](#integration-points)
7. [Code Examples](#code-examples)

---

## Architecture Overview

The HFS module follows a service-oriented architecture:

```
Controllers (API Layer)
    ↓
Services (Business Logic)
    ↓
Models (Data Access)
    ↓
Database
```

### Key Components
- **Controllers**: Handle HTTP requests, validation, responses
- **Services**: Encapsulate business logic
- **Models**: Eloquent ORM models
- **Migrations**: Database schema

---

## Database Schema

### Core Tables

#### `hfs_requests`
Primary table for HFS requests.

**Key Fields**:
- `request_no`: Unique request number (HFS-YYYY-####)
- `status`: draft, in_review, approved, rejected, cancelled, sold
- `intended_sale_date`: Expected sale date
- `is_disposal_group`: Boolean flag
- `is_overdue`: Boolean flag for 12-month rule

#### `hfs_assets`
Links assets to HFS requests.

**Key Fields**:
- `hfs_id`: Foreign key to hfs_requests
- `asset_id`: Foreign key to assets
- `asset_type`: PPE, INVENTORY, ROU, INVEST_PROP, OTHER
- `carrying_amount_at_reclass`: NBV at classification
- `current_carrying_amount`: Current NBV (after impairments)
- `depreciation_stopped`: Boolean flag

#### `hfs_valuations`
Stores valuation history.

**Key Fields**:
- `hfs_id`: Foreign key to hfs_requests
- `fair_value`: Fair value
- `costs_to_sell`: Costs to sell
- `fv_less_costs`: Calculated value
- `impairment_amount`: Impairment or reversal amount
- `impairment_journal_id`: Reference to journal

#### `hfs_disposals`
Records asset disposals.

**Key Fields**:
- `hfs_id`: Foreign key to hfs_requests
- `sale_proceeds`: Amount received
- `sale_currency`: Currency code
- `currency_rate`: Exchange rate
- `gain_loss_amount`: Calculated gain/loss
- `journal_id`: Reference to journal

#### `hfs_discontinued_flags`
Tags disposal groups as discontinued operations.

**Key Fields**:
- `hfs_id`: Foreign key to hfs_requests
- `is_discontinued`: Boolean flag
- `criteria_checked_json`: Criteria validation results
- `effects_on_pnl_json`: P&L impact data

#### `hfs_audit_logs`
Complete audit trail.

**Key Fields**:
- `hfs_id`: Foreign key to hfs_requests
- `user_id`: User who performed action
- `action`: Action type
- `old_values`: Previous state (JSON)
- `new_values`: New state (JSON)

---

## Service Layer

### HfsService
Main orchestrator for HFS operations.

**Key Methods**:
```php
createHfsRequest(array $data): HfsRequest
approveHfsRequest(HfsRequest $hfsRequest, ...): array
reclassifyToHfs(HfsRequest $hfsRequest): void
measureHfs(HfsRequest $hfsRequest, array $valuationData): array
processSale(HfsRequest $hfsRequest, array $saleData): array
cancelHfs(HfsRequest $hfsRequest, ?string $reason): array
```

### HfsValidationService
Validates IFRS 5 criteria and business rules.

**Key Methods**:
```php
validateIfrs5Criteria(HfsRequest $hfsRequest): array
validateAssetEligibility($assets): array
validateForApproval(HfsRequest $hfsRequest): array
validatePreApprovalRequirements(HfsRequest $hfsRequest): array
```

### HfsJournalService
Creates journal entries for HFS transactions.

**Key Methods**:
```php
createReclassificationJournal(HfsRequest $hfsRequest): Journal
createImpairmentJournal(HfsValuation $valuation): Journal
createReversalJournal(HfsValuation $valuation): Journal
createDisposalJournal(HfsDisposal $disposal): Journal
createCancellationJournal(HfsRequest $hfsRequest): Journal
```

### HfsMeasurementService
Calculates FV less costs, impairments, reversals.

**Key Methods**:
```php
calculateFvLessCosts(float $fairValue, float $costsToSell): float
calculateImpairment(float $carryingAmount, float $fvLessCosts): float
calculateReversal(...): float
validateMeasurementRules(...): array
```

### HfsApprovalService
Manages multi-level approval workflow.

**Key Methods**:
```php
submitForApproval(HfsRequest $hfsRequest): void
approve(HfsRequest $hfsRequest, ...): void
reject(HfsRequest $hfsRequest, ...): void
requestModification(HfsRequest $hfsRequest, ...): void
```

### HfsTaxService
Handles tax base tracking and deferred tax.

**Key Methods**:
```php
getTaxBase(Asset $asset): float
calculateDeferredTaxOnImpairment(HfsValuation $valuation): array
calculateDeferredTaxOnDisposal(HfsDisposal $disposal): array
createDeferredTaxJournalForImpairment(...): ?Journal
createDeferredTaxJournalForDisposal(...): ?Journal
```

### HfsMultiCurrencyService
Handles foreign currency transactions.

**Key Methods**:
```php
convertToFunctionalCurrency(...): float
calculateFxGainLoss(HfsDisposal $disposal): array
postFxGainLoss(HfsDisposal $disposal, float $fxGainLoss): ?Journal
```

### HfsPartialSaleService
Handles partial sales of disposal groups.

**Key Methods**:
```php
processPartialSale(HfsRequest $hfsRequest, array $saleData): array
validatePartialSale(HfsRequest $hfsRequest, array $saleData): array
```

### HfsSpecialAssetService
Handles special asset types.

**Key Methods**:
```php
isInvestmentPropertyAtFv(Asset $asset): bool
isAssetUnderConstruction(Asset $asset): bool
handleInvestmentPropertyHfs(HfsAsset $hfsAsset): void
handleAucHfs(HfsAsset $hfsAsset): void
validateDisposalGroup(HfsRequest $hfsRequest): array
```

---

## API Endpoints

### HFS Requests
```
GET    /asset-management/hfs/requests              - List HFS requests
POST   /asset-management/hfs/requests               - Create HFS request
GET    /asset-management/hfs/requests/{id}          - Show HFS request
PUT    /asset-management/hfs/requests/{id}          - Update HFS request
DELETE /asset-management/hfs/requests/{id}          - Delete HFS request
POST   /asset-management/hfs/requests/{id}/submit    - Submit for approval
POST   /asset-management/hfs/requests/{id}/approve  - Approve request
POST   /asset-management/hfs/requests/{id}/reject   - Reject request
POST   /asset-management/hfs/requests/{id}/cancel   - Cancel request
POST   /asset-management/hfs/requests/{id}/validate  - Validate criteria
```

### HFS Valuations
```
GET    /asset-management/hfs/valuations/create/{hfs_id}  - Create valuation form
POST   /asset-management/hfs/valuations                  - Store valuation
PUT    /asset-management/hfs/valuations/{id}            - Update valuation
```

### HFS Disposals
```
GET    /asset-management/hfs/disposals/create/{hfs_id}  - Create disposal form
POST   /asset-management/hfs/disposals                  - Store disposal
```

### HFS Discontinued Operations
```
POST   /asset-management/hfs/discontinued/{hfs_id}/tag  - Tag as discontinued
PUT    /asset-management/hfs/discontinued/{hfs_id}     - Update criteria
POST   /asset-management/hfs/discontinued/{hfs_id}/check - Check criteria
```

### HFS Reports
```
GET    /asset-management/hfs/reports/movement-schedule    - Movement schedule
GET    /asset-management/hfs/reports/valuation-details    - Valuation details
GET    /asset-management/hfs/reports/discontinued-ops    - Discontinued ops note
GET    /asset-management/hfs/reports/overdue            - Overdue HFS items
GET    /asset-management/hfs/reports/audit-trail         - Audit trail
```

---

## Business Logic Flow

### 1. Create HFS Request Flow
```
User creates request
    ↓
HfsService::createHfsRequest()
    ↓
Validate asset eligibility
    ↓
Create HfsRequest record
    ↓
Create HfsAsset records
    ↓
Log activity
    ↓
Return HfsRequest
```

### 2. Approval Flow
```
User submits for approval
    ↓
HfsApprovalService::submitForApproval()
    ↓
Validate IFRS 5 criteria
    ↓
Create approval records
    ↓
Notify approvers
    ↓
On final approval:
    ↓
HfsService::approveHfsRequest()
    ↓
HfsService::reclassifyToHfs()
    ↓
Create reclassification journal
    ↓
Stop depreciation
    ↓
Update asset status
```

### 3. Measurement Flow
```
User records valuation
    ↓
HfsService::measureHfs()
    ↓
HfsMeasurementService::measureHfs()
    ↓
Calculate FV less costs
    ↓
Calculate impairment/reversal
    ↓
Create HfsValuation record
    ↓
If impairment > 0:
    ↓
HfsJournalService::createImpairmentJournal()
    ↓
Post GL transactions
    ↓
Update carrying amounts
```

### 4. Disposal Flow
```
User records disposal
    ↓
HfsService::processSale()
    ↓
Calculate gain/loss
    ↓
Create HfsDisposal record
    ↓
HfsJournalService::createDisposalJournal()
    ↓
If foreign currency:
    ↓
HfsMultiCurrencyService::calculateFxGainLoss()
    ↓
HfsMultiCurrencyService::postFxGainLoss()
    ↓
If deferred tax enabled:
    ↓
HfsTaxService::createDeferredTaxJournalForDisposal()
    ↓
Update asset status to "Disposed"
```

---

## Integration Points

### Asset Module
- Extends `Asset` model with HFS fields
- Prevents depreciation when `depreciation_stopped = true`
- Updates asset status on HFS classification

### GL Module
- Uses `Journal` and `JournalItem` models
- Creates `GlTransaction` entries
- Links transactions to assets via `asset_id`

### Tax Module
- Integrates with deferred tax calculations
- Tracks tax base (unchanged on reclassification)
- Posts deferred tax journals automatically

### Reporting Module
- Hooks into financial statement generators
- Provides HFS balances for balance sheet
- Provides discontinued ops data for P&L

### Approval Module
- Uses existing approval patterns
- Extends with HFS-specific approval levels
- Integrates with notification system

---

## Code Examples

### Creating an HFS Request
```php
use App\Services\Assets\Hfs\HfsService;

$hfsService = app(HfsService::class);

$hfsRequest = $hfsService->createHfsRequest([
    'asset_ids' => [1, 2, 3],
    'intended_sale_date' => now()->addMonths(6),
    'expected_fair_value' => 100000,
    'expected_costs_to_sell' => 5000,
    'management_committed' => true,
    'management_commitment_date' => now(),
    'buyer_name' => 'ABC Company',
    'marketing_actions' => 'Active marketing program',
    'justification' => 'Strategic disposal',
]);
```

### Approving an HFS Request
```php
$result = $hfsService->approveHfsRequest(
    $hfsRequest,
    'finance_manager',
    auth()->id(),
    'Approved for HFS classification'
);
```

### Recording a Valuation
```php
$result = $hfsService->measureHfs($hfsRequest, [
    'fair_value' => 80000,
    'costs_to_sell' => 5000,
    'valuation_date' => now(),
    'valuator_name' => 'John Doe',
    'report_ref' => 'VAL-2025-001',
]);
```

### Recording a Disposal
```php
$result = $hfsService->processSale($hfsRequest, [
    'disposal_date' => now(),
    'sale_proceeds' => 90000,
    'costs_sold' => 3000,
    'sale_currency' => 'USD',
    'currency_rate' => 2500,
    'buyer_name' => 'XYZ Corp',
]);
```

### Validating IFRS 5 Criteria
```php
use App\Services\Assets\Hfs\HfsValidationService;

$validationService = app(HfsValidationService::class);
$result = $validationService->validateIfrs5Criteria($hfsRequest);

if (!$result['valid']) {
    // Handle errors
    foreach ($result['errors'] as $error) {
        // Log or display error
    }
}
```

---

## Testing

### Running Tests
```bash
# Run all HFS tests
php artisan test --filter Hfs

# Run specific test
php artisan test tests/Unit/Assets/Hfs/HfsValidationServiceTest

# Run with coverage
php artisan test --coverage
```

### Test Structure
- **Unit Tests**: Test individual service methods
- **Feature Tests**: Test complete workflows
- **Integration Tests**: Test with database

---

## Troubleshooting

### Common Issues

#### Issue: "HFS account not configured"
**Solution**: Configure HFS account in Asset Category settings

#### Issue: "Depreciation still running for HFS asset"
**Solution**: Check `depreciation_stopped` flag. For Investment Property at FV, depreciation continues per IAS 40.

#### Issue: "Journal entries not balanced"
**Solution**: Check account mappings and ensure all required accounts are configured

#### Issue: "FX rate not found"
**Solution**: Create FX rate in FX Rates module for the currency pair and date

---

## Best Practices

1. **Always use services**: Don't access models directly for business logic
2. **Wrap in transactions**: All critical operations should be in DB transactions
3. **Log everything**: Use audit log service for all changes
4. **Validate before save**: Always validate before committing to database
5. **Handle errors gracefully**: Catch exceptions and provide meaningful messages

---

## Future Enhancements

Potential improvements:
- Automated valuation updates via API
- Integration with external valuation services
- Advanced reporting with charts and graphs
- Mobile app support
- Real-time notifications

---

## Support

For technical questions or issues, contact the development team or refer to the codebase documentation.

