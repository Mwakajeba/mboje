# IFRS 5 - Held for Sale (HFS) & Discontinued Operations - Implementation Plan

## Executive Summary

This document outlines the step-by-step implementation plan for the IFRS 5 Held for Sale and Discontinued Operations module. The implementation will be done in phases to ensure systematic development, testing, and deployment.

---

## My Understanding of Requirements

### Core Objectives
1. **Reclassify assets as HFS** when IFRS 5 criteria are met (available for immediate sale, highly probable within 12 months, management committed)
2. **Stop depreciation** for HFS assets (except investment property at FV per IAS 40)
3. **Measure HFS** at lower of carrying amount and fair value less costs to sell
4. **Track disposal groups** and classify as Discontinued Operations when criteria met
5. **Generate automated journals**, audit trail, and IFRS 5 disclosures

### Key IFRS 5 Rules to Encode
- **Classification Criteria**: Asset available for immediate sale + Sale highly probable (management committed, active program, reasonable price, expected within 12 months)
- **Measurement**: Stop depreciation on classification (IFRS 5.15), except IAS 40 investment property
- **Carrying Amount**: Lower of (carrying at classification date) and (FV less costs to sell)
- **Impairment**: If FV-costs < carrying → recognize impairment loss in P&L
- **Reversals**: Subsequent increases in FV-costs can be recognized (limited by original carrying before HFS impairment)
- **Presentation**: Discontinued operations shown separately in P&L and cash flows with comparatives

---

## Implementation Phases

### **PHASE 1: Database Schema & Models** (Foundation)
**Goal**: Create all required database tables and Eloquent models

#### 1.1 Database Migrations
- [ ] `hfs_requests` table (header)
- [ ] `hfs_assets` table (line items - links to assets)
- [ ] `hfs_valuations` table (measurement history)
- [ ] `hfs_disposals` table (sale/disposal records)
- [ ] `hfs_discontinued_flags` table (discontinued operations tagging)
- [ ] `hfs_audit_logs` table (full audit trail)
- [ ] `hfs_approvals` table (multi-level approval workflow)
- [ ] Add `hfs_status` and `depreciation_stopped` fields to `assets` table
- [ ] Add `hfs_account_id` to `asset_categories` table (HFS control account)

#### 1.2 Eloquent Models
- [ ] `HfsRequest` model with relationships
- [ ] `HfsAsset` model with relationships
- [ ] `HfsValuation` model with relationships
- [ ] `HfsDisposal` model with relationships
- [ ] `HfsDiscontinuedFlag` model with relationships
- [ ] `HfsAuditLog` model
- [ ] `HfsApproval` model
- [ ] Update `Asset` model to add HFS relationships and methods

#### 1.3 Relationships & Indexes
- [ ] Foreign keys and indexes for performance
- [ ] Polymorphic relationships where needed
- [ ] Soft deletes for audit trail preservation

---

### **PHASE 2: Business Logic & Services** (Core Functionality)
**Goal**: Implement all business rules, validations, and automatic journal entries

#### 2.1 Validation Service (`HfsValidationService`)
- [ ] IFRS 5 criteria validation:
  - [ ] Asset available for immediate sale check
  - [ ] Management commitment verification (requires attachment)
  - [ ] Active program to locate buyer check
  - [ ] Reasonable price verification
  - [ ] 12-month timeline validation
  - [ ] Exception handling for >12 months (requires board approval)
- [ ] Asset eligibility checks:
  - [ ] Asset not already disposed
  - [ ] Asset not already fully impaired
  - [ ] Asset not pledged as collateral (or bank consent required)
  - [ ] Asset not already classified as HFS

#### 2.2 HFS Service (`HfsService`)
- [ ] `createHfsRequest()` - Create new HFS request with validation
- [ ] `approveHfsRequest()` - Process approval and trigger reclassification
- [ ] `reclassifyToHfs()` - Move asset to HFS, stop depreciation, post journals
- [ ] `measureHfs()` - Calculate FV less costs, post impairment if needed
- [ ] `updateValuation()` - Handle subsequent valuations and reversals
- [ ] `processSale()` - Record sale, calculate gain/loss, post disposal journals
- [ ] `cancelHfs()` - Reclassify back to original category, resume depreciation
- [ ] `checkDiscontinuedCriteria()` - Evaluate if disposal group meets discontinued ops criteria
- [ ] `tagAsDiscontinued()` - Mark as discontinued operation

#### 2.3 Journal Entry Service (`HfsJournalService`)
- [ ] **Reclassification Journal** (on approval):
  - [ ] Dr. Asset Held for Sale (HFS control account) - NBV
  - [ ] Cr. Original Asset Account (PPE/Inventory) - NBV
- [ ] **Impairment Journal** (on measurement if FV-costs < carrying):
  - [ ] Dr. Impairment Loss (P&L account)
  - [ ] Cr. Asset Held for Sale (valuation reduction)
- [ ] **Reversal Journal** (on subsequent increase, limited by original carrying):
  - [ ] Dr. Asset Held for Sale (increase)
  - [ ] Cr. Impairment Reversal (P&L account)
- [ ] **Disposal Journal** (on sale):
  - [ ] Dr. Bank/Cash (proceeds)
  - [ ] Dr. Disposal Costs (expense)
  - [ ] Dr. Accumulated Impairment (if stored separately)
  - [ ] Cr. Asset Held for Sale (carrying amount)
  - [ ] Cr/Dr. Gain/Loss on Disposal (P&L) - balancing figure
- [ ] **Cancellation Journal** (if sale cancelled):
  - [ ] Dr. Original PPE account (carrying amount)
  - [ ] Cr. Asset Held for Sale

#### 2.4 Depreciation Control Service
- [ ] `stopDepreciation()` - Set `depreciation_stopped = true` on asset
- [ ] `resumeDepreciation()` - Set `depreciation_stopped = false` and update useful life if needed
- [ ] Integration with existing depreciation service to prevent accruals when `depreciation_stopped = true`

#### 2.5 Approval Workflow Service
- [ ] Integration with existing `ApprovalService` or create HFS-specific workflow
- [ ] Multi-level approvals: Initiator → Asset Custodian → Finance Manager → CFO/Board
- [ ] Approval history tracking
- [ ] Digital signature/typed approval capture
- [ ] Rejection handling with reason

---

### **PHASE 3: Controllers & API Endpoints** (Backend API)
**Goal**: Create RESTful controllers and API routes

#### 3.1 Controllers
- [ ] `HfsRequestController` - CRUD for HFS requests
  - [ ] `index()` - List with filters (status, overdue, etc.)
  - [ ] `create()` - Show create form with asset selection
  - [ ] `store()` - Create new HFS request
  - [ ] `show()` - Display HFS request details
  - [ ] `edit()` - Edit draft requests
  - [ ] `update()` - Update draft requests
  - [ ] `submitForApproval()` - Submit to approval workflow
  - [ ] `approve()` - Approve at specific level
  - [ ] `reject()` - Reject with reason
  - [ ] `cancel()` - Cancel HFS request
- [ ] `HfsValuationController` - Manage valuations
  - [ ] `create()` - Create new valuation
  - [ ] `store()` - Save valuation and post impairment if needed
  - [ ] `update()` - Update valuation (handle reversals)
- [ ] `HfsDisposalController` - Handle sales/disposals
  - [ ] `create()` - Create disposal record
  - [ ] `store()` - Record sale and post disposal journals
- [ ] `HfsDiscontinuedController` - Manage discontinued operations
  - [ ] `tagAsDiscontinued()` - Tag disposal group as discontinued
  - [ ] `updateCriteria()` - Update discontinued criteria checks
- [ ] `HfsReportController` - Generate reports
  - [ ] `movementSchedule()` - IFRS 5 movement schedule
  - [ ] `valuationDetails()` - Valuation details report
  - [ ] `discontinuedOpsNote()` - Discontinued operations note
  - [ ] `overdueReport()` - Overdue HFS items (>12 months)
  - [ ] `auditTrail()` - Full audit trail export

#### 3.2 Routes
- [ ] Web routes for all controllers
- [ ] API routes if needed for external integrations
- [ ] Route groups with middleware (auth, permissions)

---

### **PHASE 4: User Interface (Views)** (Frontend)
**Goal**: Create all UI screens and user interactions

#### 4.1 HFS Dashboard
- [ ] Dashboard layout with cards:
  - [ ] Pending approvals count
  - [ ] Active HFS items count
  - [ ] Overdue items (>12 months) count
  - [ ] Recently sold count
  - [ ] Discontinued operations count
- [ ] DataTables for listing HFS requests with filters
- [ ] Quick actions (create, approve, view)

#### 4.2 Create HFS Request Wizard
- [ ] **Step 1: Select Assets**
  - [ ] Multi-select asset picker (PPE, Inventory, ROU, Investment Property)
  - [ ] Display carrying amounts, accumulated depreciation, NBV
  - [ ] Show linked GL accounts and location
  - [ ] Validation: check asset eligibility
- [ ] **Step 2: Sale Plan**
  - [ ] Buyer name/contact
  - [ ] Asking price / expected fair value
  - [ ] Expected costs to sell
  - [ ] Target sale date (intended_sale_date)
  - [ ] Marketing actions description
  - [ ] Probability percentage
- [ ] **Step 3: Documentation**
  - [ ] Attach management minutes (required for approval)
  - [ ] Attach valuer report (optional)
  - [ ] Attach marketing evidence
  - [ ] Other attachments
- [ ] **Step 4: Review & Submit**
  - [ ] Summary of all inputs
  - [ ] Auto-validation results
  - [ ] Submit to approval workflow

#### 4.3 Approval Workspace
- [ ] List of pending approvals (filtered by user's approval level)
- [ ] Approval form with:
  - [ ] HFS request details
  - [ ] Required checks checklist
  - [ ] Approval/reject buttons
  - [ ] Comments field
  - [ ] Digital signature or typed approval
- [ ] Approval history timeline
- [ ] Attachments viewer

#### 4.4 Valuation / Measurement Page
- [ ] Display carrying amount at classification
- [ ] Input fields:
  - [ ] Fair value
  - [ ] Costs to sell
  - [ ] Computed FV less costs (auto-calculated)
  - [ ] Suggested impairment amount
- [ ] Ability to attach valuer file
- [ ] Override capability with mandatory comment
- [ ] Post impairment button (creates journal)

#### 4.5 Asset Ledger View (Enhanced)
- [ ] Show asset lifecycle timeline:
  - [ ] Original registration
  - [ ] AUC (Assets Under Construction)
  - [ ] Capitalization
  - [ ] Revaluation(s)
  - [ ] HFS reclassification
  - [ ] Impairment(s)
  - [ ] Disposal
- [ ] Visual timeline with dates and amounts

#### 4.6 Disposal / Sale Entry
- [ ] Capture sale proceeds
- [ ] Currency and FX rate
- [ ] Bank receipts
- [ ] Disposal costs
- [ ] Auto-calculate gain/loss
- [ ] Post disposal journal button
- [ ] Mark asset as disposed

#### 4.7 Reports & Disclosures Generator
- [ ] **IFRS 5 Movement Schedule**
  - [ ] Table with columns: Asset/Disposal Group | Carrying at start | Classified during period | Impairments | Reversals | Transfers | Disposals | Carrying at end
  - [ ] Export to PDF/Excel
- [ ] **HFS Valuation Details**
  - [ ] List all valuations with dates, amounts, journals
  - [ ] Export capability
- [ ] **Discontinued Operations Note**
  - [ ] Comparative table (current year and prior year)
  - [ ] Revenue, profit/(loss), gain/(loss) on disposal, tax, total impact
- [ ] **Overdue HFS Report**
  - [ ] List items >12 months with reasons, approvals, progress notes
- [ ] **Audit Trail Export**
  - [ ] All approvals, valuations, changes, attachments

---

### **PHASE 5: Integration Points** (Connect with Existing Systems)
**Goal**: Integrate HFS module with existing asset, GL, tax, and reporting systems

#### 5.1 Asset Management Integration
- [x] Update `Asset` model to support HFS status
- [x] Prevent depreciation when `depreciation_stopped = true`
- [x] Update asset listing to show HFS status
- [x] Asset search/filter by HFS status

#### 5.2 GL Integration
- [x] Use existing `Journal` and `JournalItem` models
- [x] Create `GlTransaction` entries for all HFS journals
- [x] Link HFS journals to asset records
- [x] Ensure proper account mapping (HFS control account from category settings)

#### 5.3 Tax Integration
- [x] Track tax base per asset (unchanged on reclassification)
- [x] Deferred tax computation when impairment recorded
- [x] Deferred tax adjustment on disposal
- [x] VAT handling on sale (use existing VAT logic from disposal module)

#### 5.4 Reporting Integration
- [x] Hook into financial statement note generators
- [x] Auto-include HFS lines in balance sheet
- [x] Auto-include discontinued operations in P&L
- [x] Cash flow statement integration for discontinued ops

#### 5.5 Approval System Integration
- [x] Use existing `ApprovalService` or extend it
- [x] Create HFS-specific approval levels if needed
- [ ] Integration with notification system

---

### **PHASE 6: Business Rules & Validations** (Rules Engine)
**Goal**: Implement all IFRS 5 business rules and validations

#### 6.1 Pre-Approval Validations
- [x] Management commitment evidence required (attachment)
- [x] Buyer identified or active marketing program
- [x] Realistic timetable (≤12 months normally)
- [x] Sale price range provided
- [x] All required fields completed

#### 6.2 Auto-Flags & Alerts
- [x] Flag HFS entries older than 12 months
- [x] Require senior approval to remain open beyond 12 months
- [x] Alert on approaching 12-month deadline (e.g., 11 months)
- [x] Prevent depreciation postings when `depreciation_stopped = true`

#### 6.3 Measurement Rules
- [x] FV less costs must be ≤ carrying amount (impairment if not)
- [x] Reversals limited to original carrying before HFS impairment
- [x] Subsequent valuations must have justification

#### 6.4 Discontinued Operations Criteria
- [x] Check if disposal group represents a component of entity
- [x] Verify disposal or HFS classification
- [x] Auto-tag if criteria met
- [x] Manual override with justification

---

### **PHASE 7: Edge Cases & Special Handling** (Robustness)
**Goal**: Handle all edge cases and special scenarios

#### 7.1 Special Asset Types
- [x] Investment Property at FV (IAS 40): Continue fair value measurement, prevent double-counting
- [x] Assets Under Construction (AUC): Handle capitalized costs until sale
- [x] Disposal Groups: Support mixed asset types (PPE + Inventory + Receivables)

#### 7.2 Partial Operations
- [x] Partial sale of disposal group
- [x] Update remaining items
- [x] Reclassify remaining items if needed

#### 7.3 Collateral & Liens
- [x] Check if asset is pledged
- [x] Require bank consent record if pledged
- [x] Flag in UI and approval workflow

#### 7.4 Multi-Currency
- [x] Handle foreign currency sales
- [x] FX rate conversions
- [x] FX gains/losses posting

#### 7.5 Cancellation & Reversal
- [x] Handle sale cancellation
- [x] Reclassify back to original category
- [x] Resume depreciation (except Investment Property at FV)
- [x] Reverse journals appropriately

---

### **PHASE 8: Testing & QA** (Quality Assurance)
**Goal**: Comprehensive testing of all functionality

#### 8.1 Unit Tests
- [x] HfsService methods
- [x] HfsJournalService journal creation
- [x] Validation service rules
- [x] Measurement calculations

#### 8.2 Integration Tests
- [x] Full HFS workflow (create → approve → measure → sell)
- [x] Journal entry accuracy
- [x] GL transaction creation
- [x] Depreciation prevention
- [x] Approval workflow

#### 8.3 Scenario Tests
- [x] Classification path with impairment
- [x] Reversal scenario
- [x] Sale flow with gain/loss
- [x] Discontinued operations presentation
- [x] Overdue handling
- [x] Multi-currency sale
- [x] Partial sale
- [x] Cancellation

#### 8.4 Data Integrity Tests
- [x] Foreign key constraints (enforced by migrations)
- [x] Transaction rollback on errors (DB transactions in services)
- [x] Audit trail immutability (audit log service)
- [x] Attachment storage (JSON field support)

---

### **PHASE 9: Documentation & Training** (Knowledge Transfer)
**Goal**: Create documentation for users and developers

#### 9.1 User Documentation
- [x] User guide for HFS workflow
- [x] Step-by-step tutorials
- [x] Sample scenarios (classification, impairment, reversal, sale)
- [x] FAQ

#### 9.2 Developer Documentation
- [x] API documentation
- [x] Database schema documentation
- [x] Business logic flowcharts
- [x] Code comments and PHPDoc

#### 9.3 Auditor Documentation
- [x] IFRS 5 compliance checklist
- [x] Disclosure requirements
- [x] Audit trail explanation
- [x] Sample reports

---

## Technical Architecture Decisions

### Database Design
- **Primary Tables**: `hfs_requests` (header), `hfs_assets` (lines), `hfs_valuations` (measurements), `hfs_disposals` (sales), `hfs_discontinued_flags` (tagging), `hfs_audit_logs` (audit)
- **Relationships**: One-to-many (request → assets, request → valuations), Many-to-one (assets → original asset)
- **Indexes**: On `hfs_id`, `asset_id`, `status`, `created_at`, `intended_sale_date` for performance
- **Soft Deletes**: On `hfs_requests` and `hfs_assets` for audit trail

### Service Layer Pattern
- **HfsService**: Main business logic orchestrator
- **HfsValidationService**: All validation rules
- **HfsJournalService**: Journal entry creation
- **HfsMeasurementService**: FV calculations and impairment logic
- **HfsApprovalService**: Approval workflow management

### Integration Points
- **Existing Asset Module**: Extend `Asset` model, prevent depreciation
- **Existing GL Module**: Use `Journal`, `JournalItem`, `GlTransaction`
- **Existing Approval Module**: Extend `ApprovalService` or create HFS-specific
- **Existing Tax Module**: Deferred tax calculations
- **Existing Reporting Module**: Hook into note generators

---

## Implementation Order (Recommended Sequence)

1. **Phase 1** (Database & Models) - Foundation
2. **Phase 2** (Business Logic) - Core functionality
3. **Phase 3** (Controllers) - API layer
4. **Phase 4** (UI) - User interface
5. **Phase 5** (Integration) - Connect with existing systems
6. **Phase 6** (Business Rules) - Validation and rules
7. **Phase 7** (Edge Cases) - Special handling
8. **Phase 8** (Testing) - Quality assurance
9. **Phase 9** (Documentation) - Knowledge transfer

---

## Success Criteria

### Functional Requirements
- ✅ Users can create HFS requests and select assets
- ✅ IFRS 5 criteria are validated before approval
- ✅ Multi-level approval workflow works correctly
- ✅ Assets are reclassified to HFS with correct journals
- ✅ Depreciation stops automatically
- ✅ Impairments are calculated and posted correctly
- ✅ Reversals are limited appropriately
- ✅ Sales are recorded with correct gain/loss
- ✅ Discontinued operations are tagged and presented correctly
- ✅ All required reports are generated

### Non-Functional Requirements
- ✅ Performance: Handle large asset registers (pagination)
- ✅ Security: Role-based access control
- ✅ Auditability: Immutable audit trail
- ✅ Usability: Intuitive UI with clear workflows
- ✅ Compliance: IFRS 5 compliant

---

## Questions for Confirmation

Before proceeding, please confirm:

1. **Approval Workflow**: Should we use the existing `ApprovalService` or create HFS-specific approval tables? (I recommend extending existing for consistency)

2. **HFS Control Account**: Should this be configurable per asset category, or a single system-wide account? (I recommend per category for flexibility)

3. **12-Month Rule**: Should extensions beyond 12 months require board approval automatically, or just flag for review? (I recommend requiring explicit approval)

4. **Discontinued Operations**: Should the system auto-detect based on criteria, or require manual tagging? (I recommend auto-detect with manual override)

5. **Investment Property**: For IAS 40 investment property at FV, should we continue FV measurement or switch to cost model for HFS? (I recommend continue FV per IAS 40)

6. **Partial Sales**: Should we support partial sale of a disposal group? (I recommend yes, with remaining items staying HFS or reclassifying)

7. **Multi-Currency**: Are foreign currency sales common? (Affects priority of multi-currency features)

8. **Reporting**: Should reports be integrated into existing financial statement generator, or standalone? (I recommend integrated)

---

## Next Steps

Once you confirm this plan and answer the questions above, I will proceed with:

1. **Phase 1**: Creating database migrations and models
2. **Phase 2**: Implementing core business logic services
3. **Phase 3**: Building controllers and API endpoints
4. **Phase 4**: Creating UI screens
5. **Phase 5**: Integrating with existing systems

**Please review this plan and let me know:**
- ✅ If the understanding is correct
- ✅ If any phases need adjustment
- ✅ Answers to the confirmation questions
- ✅ Any additional requirements or constraints

Then I'll proceed step-by-step with your approval at each phase!

