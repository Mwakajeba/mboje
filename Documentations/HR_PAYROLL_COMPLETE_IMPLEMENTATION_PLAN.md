# HR & Payroll Management - Complete Implementation Plan
## Enterprise-Grade System for Tanzania

**Document Version:** 2.2  
**Date:** January 2025  
**Status:** Phase 2 Complete - Phase 3 Mostly Complete - Phase 6 Added (Employment Lifecycle Management)

---

## Executive Summary

This document outlines the complete implementation plan for transforming the existing HR & Payroll module into an enterprise-grade system compliant with Tanzanian employment laws and best practices.

**Current State:** Enhanced HR & Payroll with employee management, payroll processing, complete leave management, attendance system, work schedules, shifts, overtime rules & approvals, and payroll calendar integration.

**Target State:** Complete 9-stage HR & Payroll system with attendance, performance, training, discipline, ESS/MSS, and advanced analytics.

**Estimated Timeline:** 6-8 months (phased approach)  
**Team Required:** 3-4 developers + 1 QA + 1 Business Analyst + 1 HR Subject Matter Expert

---

## Table of Contents

1. [Current State Assessment](#current-state-assessment)
2. [Gap Analysis](#gap-analysis)
3. [Implementation Roadmap](#implementation-roadmap)
4. [Stage-by-Stage Implementation Details](#stage-by-stage-implementation-details)
5. [Technical Architecture](#technical-architecture)
6. [Database Schema Design](#database-schema-design)
7. [Integration Points](#integration-points)
8. [Testing Strategy](#testing-strategy)
9. [Deployment Plan](#deployment-plan)

---

## Current State Assessment

### ✅ What Exists

1. **Core HR Models**
   - `Employee` model with basic fields
   - `Department`, `Position` models
   - `Document` model for file management
   - Basic employee CRUD operations

2. **Payroll System**
   - `Payroll` model with approval workflow
   - `PayrollEmployee` model
   - Statutory deductions (PAYE, NHIF, Pension, WCF, SDL, HESLB)
   - Payroll processing and approval
   - GL integration for payroll accounting

3. **Leave Management (Complete)**
   - `LeaveType`, `LeaveRequest`, `LeaveBalance` models
   - Leave accrual system
   - Leave approval workflow
   - Public holiday management
   - Document attachments
   - Leave request submission workflow
   - Enhanced UI and authorization

4. **Financial Components**
   - `Allowance`, `SalaryAdvance`, `ExternalLoan` models
   - Trade union deductions
   - Payroll chart account mapping

5. **Time & Attendance System (Complete)**
   - `Attendance` model with clock in/out tracking
   - `WorkSchedule` and `Shift` models
   - `EmployeeSchedule` assignments
   - Exception handling (late, early exit, missing punch, absent)
   - Overtime calculation and approval
   - Integration with payroll processing

6. **Overtime Management (Complete)**
   - `OvertimeRule` model (grade-based, day-type rates)
   - `OvertimeRequest` model with approval workflow
   - `OvertimeApprovalSettings` for multi-level approvals
   - Full integration with payroll calculation

7. **Basic Features**
   - Employee import/export
   - Payslip generation
   - Approval workflows (enhanced)
   - Payroll calendar integration
   - Payment approval workflow

### ❌ What's Missing

1. **Stage 1: Dashboard** - No comprehensive HR dashboard
2. **Stage 2: Core HR Enhancements**
   - No effective-dated records
   - No position control/headcount management
   - No job grades & salary bands
   - No contract management
   - No employment status history
   - No compliance tracking engine
   - No cost center assignment
   - No reporting hierarchies

3. **Stage 3: Time & Attendance** ✅ **COMPLETE**
   - ✅ Attendance management system implemented
   - ✅ Work schedules & shifts implemented
   - ✅ Overtime rules & approvals implemented
   - ✅ Exception handling implemented
   - ⚠️ Biometric integration (partial - service exists, UI may need enhancement)

4. **Stage 4: Payroll Enhancements** ⚠️ **PARTIALLY COMPLETE**
   - ✅ Payroll calendar implemented
   - ❌ Missing pay groups
   - ❌ Missing salary structure engine
   - ⚠️ Statutory engine exists but may need enhancement
   - ⚠️ Payroll locking controls (partial - calendar locking exists)

5. **Stage 5: ESS/MSS**
   - No employee self-service portal
   - No manager self-service portal
   - No workflow engine
   - No notifications system

6. **Stage 6: Performance & Training**
   - No performance management
   - No KPI framework
   - No appraisal system
   - No training management
   - No skills & competency framework
   - No training bonds

7. **Stage 7: Discipline & Exit**
   - No disciplinary case management
   - No grievance management
   - No exit management
   - No final payroll automation

8. **Stage 8: Reporting & Analytics**
   - Limited operational reports
   - No comprehensive dashboards
   - No statutory compliance reports
   - No workforce analytics

9. **Stage 9: Advanced Analytics**
   - No payroll forecasting
   - No workforce trend analysis
   - No attrition risk analysis
   - No scenario planning

---

## Gap Analysis

### Critical Gaps (Must Have)

| Module | Gap | Impact | Priority |
|--------|-----|--------|----------|
| Attendance | ✅ Implemented | Payroll can calculate overtime/absence | ✅ **COMPLETE** |
| Position Control | No headcount management | Risk of overstaffing, budget overruns | **P0** |
| Contract Management | No contract tracking | Legal compliance risk | **P0** |
| Payroll Calendar | ✅ Implemented | Payroll timing managed | ✅ **COMPLETE** |
| ESS/MSS | Complete absence | High HR workload, poor employee experience | **P1** |
| Performance | Complete absence | No merit-based decisions | **P1** |
| Training | Complete absence | No skills development tracking | **P1** |
| Discipline | Complete absence | Legal compliance risk | **P1** |
| Exit Management | Complete absence | Final payroll errors | **P1** |
| Dashboards | Basic only | Poor management visibility | **P2** |
| Advanced Analytics | Complete absence | No strategic insights | **P2** |

---

## Implementation Roadmap

### Phase 1: Foundation & Core HR Enhancement (Weeks 1-6)
**Focus:** Strengthen Stage 2 (Core HR) - Foundation for everything else

**Deliverables:**
- Enhanced Employee Master with effective-dated records
- Position Control & Headcount Management
- Job Grades & Salary Bands
- Contract Management
- Employment Status History
- Compliance Tracking Engine
- Cost Center Assignment
- Reporting Hierarchies

**Dependencies:** None (foundation)

---

### Phase 2: Time, Attendance & Leave Enhancement (Weeks 7-10) ✅ **COMPLETE**
**Focus:** Complete Stage 3 - Bridge between HR and Payroll

**Deliverables:** ✅ **ALL COMPLETED**
- ✅ Attendance Management System
- ✅ Work Schedules & Shifts
- ✅ Overtime Rules & Approvals
- ✅ Exception Handling
- ✅ Enhanced Leave Management
- ✅ Integration with Payroll

**Status:** All Phase 2 deliverables have been implemented and integrated with payroll processing.

**Dependencies:** Phase 1 (needs employee & position data)

---

### Phase 3: Payroll Enhancement & Statutory Compliance (Weeks 11-14) ✅ **MOSTLY COMPLETE**
**Focus:** Complete Stage 4 - Accurate, compliant payroll

**Deliverables:**
- ✅ Payroll Calendar (implemented and integrated)
- ✅ Pay Groups (infrastructure and integration complete)
  - ✅ Integration into payroll processing — payroll filters employees by pay group
  - ✅ Pay group-based payroll runs — can process payroll for a specific pay group
  - ✅ Pay group calendar integration — pay groups cut-off/pay days used when no calendar selected
  - ⚠️ Pay group-specific rules — different statutory rules per pay group (future enhancement)
- ✅ Salary Structure Engine (complete with bulk assignment, templates, and validation)
  - ✅ UI/UX improvements for managing employee salary structures
  - ✅ Bulk assignment of salary structures
  - ✅ Salary structure templates
  - ✅ Better validation and error handling
- ⚠️ Enhanced Statutory Engine (Tanzania) - basic implementation exists, needs more comprehensive Tanzania-specific rules
- ✅ Payroll Locking & Audit Controls (complete)
  - ✅ Payroll-level locking (prevent changes after processing)
  - ✅ Comprehensive audit log (who changed what and when)
  - ✅ Reversal/correction workflow
  - ✅ Approval workflow audit trail
- ⚠️ Comprehensive Payroll Reports (index page created with report cards, individual reports pending implementation)

**Status:** Payroll calendar, payment approval workflow, pay groups integration, salary structure engine, and payroll locking & audit controls are complete. Enhanced statutory engine needs more comprehensive rules. Payroll reports index page created, individual reports need implementation.

**Dependencies:** Phase 1, Phase 2 (needs attendance/leave data) ✅ **COMPLETE**

---

### Phase 4: Employee & Manager Self-Service (Weeks 15-18)
**Focus:** Stage 5 - Reduce HR workload, improve UX

**Deliverables:**
- Employee Self-Service Portal
- Manager Self-Service Portal
- Workflow & Approval Engine
- Notifications System
- Mobile-Friendly Design

**Dependencies:** Phase 1, Phase 2, Phase 3 (needs all core data)

---

### Phase 5: Performance & Training (Weeks 19-22)
**Focus:** Stage 6 - Merit-based decisions

**Deliverables:**
- Performance Management System
- KPI Framework
- Appraisal System
- Training Management
- Skills & Competency Framework
- Training Bonds

**Dependencies:** Phase 1, Phase 4 (needs employee data, ESS for appraisals)

---

### Phase 6: Employment Lifecycle Management (Weeks 23-28)
**Focus:** Complete employee lifecycle from recruitment to exit

**Deliverables:**

#### 6.1 Recruitment (Optional but Recommended)
- **Vacancy Requisition**
  - Create and manage job vacancies
  - Requisition approval workflow
  - Budget impact analysis
  - Headcount impact tracking
  
- **Applicant Database**
  - Centralized applicant records
  - Resume/CV storage
  - Application tracking
  - Duplicate detection
  
- **Interview Records**
  - Interview scheduling
  - Interview feedback forms
  - Interview scoring/rubrics
  - Candidate evaluation
  
- **Offer Letters**
  - Offer letter generation
  - Offer approval workflow
  - Salary negotiation tracking
  - Acceptance tracking
  
- **Conversion to Employee Master**
  - Seamless conversion from applicant to employee
  - Data migration automation
  - Employee number assignment
  - Onboarding trigger

#### 6.2 Onboarding
- **Checklist-based Onboarding**
  - Configurable onboarding checklists
  - Department-specific checklists
  - Role-based checklists
  - Progress tracking
  
- **Mandatory Documents Upload**
  - Document requirement management
  - Document upload tracking
  - Document verification
  - Compliance document checks
  
- **Policy Acknowledgment**
  - Policy distribution
  - Digital signature/acknowledgment
  - Policy acceptance tracking
  - Compliance verification
  
- **Payroll Eligibility Activation**
  - Automatic activation upon completion
  - Integration with payroll system
  - First payroll inclusion
  - Initial pay setup

#### 6.3 Confirmation
- **Probation Review**
  - Probation period tracking
  - Review reminders
  - Performance evaluation during probation
  - Extend/terminate probation workflows
  
- **Confirmation Approval**
  - Confirmation request workflow
  - Manager approval
  - HR approval
  - Final confirmation
  
- **Salary Adjustments**
  - Automatic salary adjustments upon confirmation
  - Confirmation bonus tracking
  - Backdated salary adjustments
  - Payroll impact automation

#### 6.4 Transfers & Promotions
- **Department Changes**
  - Transfer request workflow
  - Approval process
  - Effective date management
  - Cost center reassignment
  
- **Job Grade Changes**
  - Promotion/demotion tracking
  - Grade change approval
  - Effective dating
  - Historical tracking
  
- **Salary Impact Automation**
  - Automatic salary adjustment calculations
  - Transfer allowances
  - Promotion increments
  - Effective date-based payroll impact
  - Retroactive adjustments

**Dependencies:** Phase 1 (needs employee master, positions, departments), Phase 3 (needs payroll for salary adjustments)

---

### Phase 7: Discipline, Grievance & Exit (Weeks 29-32)
**Focus:** Stage 7 - Legal compliance & employee lifecycle end

**Deliverables:**
- Disciplinary Case Management
- Grievance Management
- Exit Management
- Final Payroll Automation
- Clearance Checklists

**Dependencies:** Phase 1, Phase 3, Phase 4, Phase 6 (needs employee, payroll, ESS, lifecycle data)

---

### Phase 8: Reporting, Analytics & Dashboards (Weeks 33-36)
**Focus:** Stage 8 & 9 - Management insights

**Deliverables:**
- Comprehensive HR Dashboard
- Operational Reports
- Statutory Compliance Reports
- Workforce Analytics
- Payroll Forecasting
- Advanced Analytics & KPIs

**Dependencies:** All previous phases (needs all data)

---

## Stage-by-Stage Implementation Details

### STAGE 1: HR & PAYROLL DASHBOARD

#### 1.1 Database Schema

```sql
-- Dashboard widgets configuration
CREATE TABLE hr_dashboard_widgets (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    company_id BIGINT UNSIGNED NOT NULL,
    widget_type VARCHAR(50) NOT NULL, -- 'headcount', 'payroll_cost', 'attendance', etc.
    widget_config JSON,
    position INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Dashboard user preferences
CREATE TABLE hr_dashboard_user_preferences (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    company_id BIGINT UNSIGNED NOT NULL,
    widget_layout JSON, -- Stores widget positions
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

#### 1.2 Models to Create

- `Hr\DashboardWidget`
- `Hr\DashboardUserPreference`

#### 1.3 Controllers to Create/Update

- `Hr\DashboardController` - Main dashboard
- Methods:
  - `index()` - Main dashboard view
  - `getHeadcountData()` - Headcount statistics
  - `getPayrollCostData()` - Payroll cost trends
  - `getAttendanceStats()` - Attendance statistics
  - `getPendingApprovals()` - Pending approvals count
  - `getComplianceAlerts()` - Compliance warnings
  - `getContractExpiryAlerts()` - Contract expiry warnings
  - `getLeaveLiability()` - Leave liability calculation

#### 1.4 Key Features

- **Real-time KPIs**: Pull from approved/posted data only
- **Role-based visibility**: HR sees all, Payroll sees payroll only, Managers see team only
- **Widget-based layout**: Drag-and-drop customizable dashboard
- **Color-coded alerts**: Green (OK), Yellow (Warning), Red (Critical)

---

### STAGE 2: CORE HR ENHANCEMENT

#### 2.1 Database Schema Additions

```sql
-- Employment status history
CREATE TABLE hr_employment_status_history (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    employee_id BIGINT UNSIGNED NOT NULL,
    status VARCHAR(50) NOT NULL, -- 'active', 'suspended', 'lwop', 'exited'
    effective_date DATE NOT NULL,
    end_date DATE NULL,
    reason TEXT,
    changed_by BIGINT UNSIGNED,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Contracts
CREATE TABLE hr_contracts (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    employee_id BIGINT UNSIGNED NOT NULL,
    contract_type VARCHAR(50) NOT NULL, -- 'permanent', 'fixed_term', 'probation', etc.
    start_date DATE NOT NULL,
    end_date DATE NULL,
    working_hours_per_week INT DEFAULT 40,
    salary_reference DECIMAL(15,2),
    renewal_flag BOOLEAN DEFAULT FALSE,
    status VARCHAR(50) DEFAULT 'active', -- 'active', 'expired', 'terminated'
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Contract amendments
CREATE TABLE hr_contract_amendments (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    contract_id BIGINT UNSIGNED NOT NULL,
    amendment_type VARCHAR(50), -- 'salary_change', 'role_change', 'extension'
    effective_date DATE NOT NULL,
    old_value JSON,
    new_value JSON,
    reason TEXT,
    approved_by BIGINT UNSIGNED,
    created_at TIMESTAMP
);

-- Job grades
CREATE TABLE hr_job_grades (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    company_id BIGINT UNSIGNED NOT NULL,
    grade_code VARCHAR(20) NOT NULL,
    grade_name VARCHAR(100) NOT NULL,
    minimum_salary DECIMAL(15,2),
    midpoint_salary DECIMAL(15,2),
    maximum_salary DECIMAL(15,2),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Position control
CREATE TABLE hr_positions (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    company_id BIGINT UNSIGNED NOT NULL,
    department_id BIGINT UNSIGNED,
    position_code VARCHAR(50) NOT NULL,
    position_title VARCHAR(200) NOT NULL,
    job_description TEXT,
    grade_id BIGINT UNSIGNED,
    approved_headcount INT DEFAULT 1,
    filled_headcount INT DEFAULT 0,
    budgeted_salary DECIMAL(15,2),
    status VARCHAR(50) DEFAULT 'approved', -- 'approved', 'frozen', 'cancelled'
    effective_date DATE,
    end_date DATE NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Position assignments (effective-dated)
CREATE TABLE hr_position_assignments (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    employee_id BIGINT UNSIGNED NOT NULL,
    position_id BIGINT UNSIGNED NOT NULL,
    effective_date DATE NOT NULL,
    end_date DATE NULL,
    is_acting BOOLEAN DEFAULT FALSE,
    acting_allowance_percent DECIMAL(5,2) DEFAULT 0,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Compliance tracking
CREATE TABLE hr_employee_compliance (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    employee_id BIGINT UNSIGNED NOT NULL,
    compliance_type VARCHAR(50) NOT NULL, -- 'paye', 'pension', 'nhif', 'wcf', 'sdl'
    compliance_number VARCHAR(100),
    is_valid BOOLEAN DEFAULT FALSE,
    expiry_date DATE NULL,
    last_verified_at TIMESTAMP NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Cost centers
CREATE TABLE hr_employee_cost_centers (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    employee_id BIGINT UNSIGNED NOT NULL,
    cost_center_code VARCHAR(50) NOT NULL,
    allocation_percent DECIMAL(5,2) DEFAULT 100.00,
    effective_date DATE NOT NULL,
    end_date DATE NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

#### 2.2 Models to Create

- `Hr\EmploymentStatusHistory`
- `Hr\Contract`
- `Hr\ContractAmendment`
- `Hr\JobGrade`
- `Hr\Position` (enhance existing)
- `Hr\PositionAssignment`
- `Hr\EmployeeCompliance`
- `Hr\EmployeeCostCenter`

#### 2.3 Key Features

- **Effective-dated records**: All changes tracked with effective dates
- **Position control**: Prevents overstaffing, tracks headcount
- **Compliance engine**: Blocks payroll if compliance incomplete
- **Cost center allocation**: Multi-cost center support for payroll costing

---

### STAGE 3: TIME, ATTENDANCE & LEAVE

#### 3.1 Database Schema

```sql
-- Work schedules
CREATE TABLE hr_work_schedules (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    company_id BIGINT UNSIGNED NOT NULL,
    schedule_code VARCHAR(50) NOT NULL,
    schedule_name VARCHAR(200) NOT NULL,
    weekly_pattern JSON, -- {'monday': true, 'tuesday': true, ...}
    standard_daily_hours DECIMAL(4,2) DEFAULT 8.00,
    break_duration_minutes INT DEFAULT 60,
    overtime_eligible BOOLEAN DEFAULT TRUE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Shifts
CREATE TABLE hr_shifts (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    company_id BIGINT UNSIGNED NOT NULL,
    shift_code VARCHAR(50) NOT NULL,
    shift_name VARCHAR(200) NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    crosses_midnight BOOLEAN DEFAULT FALSE,
    shift_differential_percent DECIMAL(5,2) DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Employee schedule assignments
CREATE TABLE hr_employee_schedules (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    employee_id BIGINT UNSIGNED NOT NULL,
    schedule_id BIGINT UNSIGNED,
    shift_id BIGINT UNSIGNED,
    effective_date DATE NOT NULL,
    end_date DATE NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Attendance records
CREATE TABLE hr_attendance (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    employee_id BIGINT UNSIGNED NOT NULL,
    attendance_date DATE NOT NULL,
    schedule_id BIGINT UNSIGNED,
    shift_id BIGINT UNSIGNED,
    clock_in TIME,
    clock_out TIME,
    expected_hours DECIMAL(4,2),
    actual_hours DECIMAL(4,2),
    normal_hours DECIMAL(4,2),
    overtime_hours DECIMAL(4,2),
    late_minutes INT DEFAULT 0,
    early_exit_minutes INT DEFAULT 0,
    status VARCHAR(50) DEFAULT 'present', -- 'present', 'absent', 'late', 'early_exit'
    exception_type VARCHAR(50), -- 'late', 'early_exit', 'missing_punch', 'absent'
    exception_reason TEXT,
    is_approved BOOLEAN DEFAULT FALSE,
    approved_by BIGINT UNSIGNED,
    approved_at TIMESTAMP NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    UNIQUE KEY unique_employee_date (employee_id, attendance_date)
);

-- Overtime requests
CREATE TABLE hr_overtime_requests (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    employee_id BIGINT UNSIGNED NOT NULL,
    attendance_id BIGINT UNSIGNED,
    overtime_date DATE NOT NULL,
    overtime_hours DECIMAL(4,2) NOT NULL,
    overtime_rate DECIMAL(5,2) DEFAULT 1.50, -- 1.5x, 2x, etc.
    reason TEXT,
    status VARCHAR(50) DEFAULT 'pending', -- 'pending', 'approved', 'rejected'
    approved_by BIGINT UNSIGNED,
    approved_at TIMESTAMP NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Overtime rules
CREATE TABLE hr_overtime_rules (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    company_id BIGINT UNSIGNED NOT NULL,
    grade_id BIGINT UNSIGNED NULL, -- NULL = applies to all
    day_type VARCHAR(50) NOT NULL, -- 'weekday', 'weekend', 'holiday'
    overtime_rate DECIMAL(5,2) NOT NULL,
    max_hours_per_day DECIMAL(4,2),
    requires_approval BOOLEAN DEFAULT TRUE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

#### 3.2 Models to Create

- `Hr\WorkSchedule`
- `Hr\Shift`
- `Hr\EmployeeSchedule`
- `Hr\Attendance`
- `Hr\OvertimeRequest`
- `Hr\OvertimeRule`

#### 3.3 Key Features

- **Multiple capture methods**: Manual, biometric, hybrid
- **Automatic overtime calculation**: Based on rules and approvals
- **Exception handling**: Late, early exit, absence flags
- **Integration with payroll**: Sends hours, OT, absence to payroll

---

### STAGE 4: PAYROLL ENHANCEMENT

#### 4.1 Database Schema Additions

```sql
-- Payroll calendar
CREATE TABLE hr_payroll_calendars (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    company_id BIGINT UNSIGNED NOT NULL,
    calendar_year INT NOT NULL,
    payroll_month INT NOT NULL, -- 1-12
    cut_off_date DATE NOT NULL,
    pay_date DATE NOT NULL,
    is_locked BOOLEAN DEFAULT FALSE,
    locked_at TIMESTAMP NULL,
    locked_by BIGINT UNSIGNED,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Pay groups
CREATE TABLE hr_pay_groups (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    company_id BIGINT UNSIGNED NOT NULL,
    pay_group_code VARCHAR(50) NOT NULL,
    pay_group_name VARCHAR(200) NOT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Employee pay group assignments
CREATE TABLE hr_employee_pay_groups (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    employee_id BIGINT UNSIGNED NOT NULL,
    pay_group_id BIGINT UNSIGNED NOT NULL,
    effective_date DATE NOT NULL,
    end_date DATE NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Salary components
CREATE TABLE hr_salary_components (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    company_id BIGINT UNSIGNED NOT NULL,
    component_code VARCHAR(50) NOT NULL,
    component_name VARCHAR(200) NOT NULL,
    component_type VARCHAR(50) NOT NULL, -- 'earning', 'deduction'
    is_taxable BOOLEAN DEFAULT TRUE,
    is_pensionable BOOLEAN DEFAULT FALSE,
    is_nhif_applicable BOOLEAN DEFAULT TRUE,
    calculation_type VARCHAR(50) DEFAULT 'fixed', -- 'fixed', 'formula', 'percentage'
    calculation_formula TEXT,
    ceiling_amount DECIMAL(15,2) NULL,
    floor_amount DECIMAL(15,2) NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Employee salary structure
CREATE TABLE hr_employee_salary_structure (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    employee_id BIGINT UNSIGNED NOT NULL,
    component_id BIGINT UNSIGNED NOT NULL,
    amount DECIMAL(15,2),
    percentage DECIMAL(5,2),
    effective_date DATE NOT NULL,
    end_date DATE NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

#### 4.2 Models to Create

- `Hr\PayrollCalendar`
- `Hr\PayGroup`
- `Hr\EmployeePayGroup`
- `Hr\SalaryComponent`
- `Hr\EmployeeSalaryStructure`

#### 4.3 Key Features

- **Payroll calendar**: Automated cut-off and pay dates
- **Pay groups**: Different rules for different employee types
- **Salary structure engine**: Flexible component-based salaries
- **Enhanced statutory engine**: Full Tanzania compliance

---

### STAGE 5: ESS/MSS

#### 5.1 Database Schema

```sql
-- ESS requests
CREATE TABLE hr_ess_requests (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    employee_id BIGINT UNSIGNED NOT NULL,
    request_type VARCHAR(50) NOT NULL, -- 'profile_update', 'leave', 'advance', 'loan', 'letter'
    request_data JSON,
    status VARCHAR(50) DEFAULT 'pending', -- 'pending', 'approved', 'rejected', 'cancelled'
    current_approval_level INT DEFAULT 1,
    approved_by BIGINT UNSIGNED,
    approved_at TIMESTAMP NULL,
    rejected_by BIGINT UNSIGNED,
    rejected_at TIMESTAMP NULL,
    rejection_reason TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- ESS notifications
CREATE TABLE hr_ess_notifications (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    notification_type VARCHAR(50) NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT,
    link_url VARCHAR(500),
    is_read BOOLEAN DEFAULT FALSE,
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP
);

-- Workflow definitions
CREATE TABLE hr_workflow_definitions (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    company_id BIGINT UNSIGNED NOT NULL,
    workflow_type VARCHAR(50) NOT NULL, -- 'leave', 'advance', 'profile_update'
    workflow_name VARCHAR(200) NOT NULL,
    approval_levels JSON, -- Array of approval levels with roles/users
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

#### 5.2 Models to Create

- `Hr\EssRequest`
- `Hr\EssNotification`
- `Hr\WorkflowDefinition`

#### 5.3 Key Features

- **Employee portal**: Self-service for leave, advances, profile updates
- **Manager portal**: Approvals, team visibility
- **Workflow engine**: Configurable multi-level approvals
- **Notifications**: In-system, email, optional SMS

---

### STAGE 6: PERFORMANCE & TRAINING

#### 6.1 Database Schema

```sql
-- KPIs
CREATE TABLE hr_kpis (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    company_id BIGINT UNSIGNED NOT NULL,
    kpi_code VARCHAR(50) NOT NULL,
    kpi_name VARCHAR(200) NOT NULL,
    description TEXT,
    measurement_criteria TEXT,
    weight_percent DECIMAL(5,2),
    target_value DECIMAL(10,2),
    scoring_method VARCHAR(50) DEFAULT 'numeric', -- 'numeric', 'rating_scale'
    applicable_to VARCHAR(50) DEFAULT 'individual', -- 'company', 'department', 'position', 'individual'
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Appraisal cycles
CREATE TABLE hr_appraisal_cycles (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    company_id BIGINT UNSIGNED NOT NULL,
    cycle_name VARCHAR(200) NOT NULL,
    cycle_type VARCHAR(50) NOT NULL, -- 'annual', 'semi_annual', 'quarterly', 'probation'
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status VARCHAR(50) DEFAULT 'draft', -- 'draft', 'active', 'completed', 'cancelled'
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Appraisals
CREATE TABLE hr_appraisals (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    employee_id BIGINT UNSIGNED NOT NULL,
    cycle_id BIGINT UNSIGNED NOT NULL,
    appraiser_id BIGINT UNSIGNED NOT NULL, -- Line manager
    self_assessment_score DECIMAL(5,2) NULL,
    manager_score DECIMAL(5,2) NULL,
    final_score DECIMAL(5,2) NULL,
    rating VARCHAR(50), -- 'excellent', 'good', 'average', 'needs_improvement'
    status VARCHAR(50) DEFAULT 'draft', -- 'draft', 'submitted', 'approved', 'locked'
    approved_by BIGINT UNSIGNED,
    approved_at TIMESTAMP NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Appraisal KPI scores
CREATE TABLE hr_appraisal_kpi_scores (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    appraisal_id BIGINT UNSIGNED NOT NULL,
    kpi_id BIGINT UNSIGNED NOT NULL,
    self_score DECIMAL(5,2) NULL,
    manager_score DECIMAL(5,2) NULL,
    final_score DECIMAL(5,2) NULL,
    comments TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Training programs
CREATE TABLE hr_training_programs (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    company_id BIGINT UNSIGNED NOT NULL,
    program_code VARCHAR(50) NOT NULL,
    program_name VARCHAR(200) NOT NULL,
    provider VARCHAR(200), -- 'internal', 'external'
    cost DECIMAL(15,2),
    duration_days INT,
    funding_source VARCHAR(50), -- 'sdl', 'internal', 'donor'
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Training attendance
CREATE TABLE hr_training_attendance (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    program_id BIGINT UNSIGNED NOT NULL,
    employee_id BIGINT UNSIGNED NOT NULL,
    attendance_status VARCHAR(50) DEFAULT 'registered', -- 'registered', 'attended', 'completed', 'absent'
    completion_date DATE NULL,
    certification_received BOOLEAN DEFAULT FALSE,
    evaluation_score DECIMAL(5,2) NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Skills inventory
CREATE TABLE hr_employee_skills (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    employee_id BIGINT UNSIGNED NOT NULL,
    skill_name VARCHAR(200) NOT NULL,
    skill_level VARCHAR(50), -- 'beginner', 'intermediate', 'advanced', 'expert'
    certification_name VARCHAR(200),
    certification_expiry DATE NULL,
    verified_by BIGINT UNSIGNED,
    verified_at TIMESTAMP NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Training bonds
CREATE TABLE hr_training_bonds (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    employee_id BIGINT UNSIGNED NOT NULL,
    training_program_id BIGINT UNSIGNED NOT NULL,
    bond_amount DECIMAL(15,2) NOT NULL,
    bond_period_months INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    recovery_rules JSON,
    status VARCHAR(50) DEFAULT 'active', -- 'active', 'fulfilled', 'recovered'
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

#### 6.2 Models to Create

- `Hr\Kpi`
- `Hr\AppraisalCycle`
- `Hr\Appraisal`
- `Hr\AppraisalKpiScore`
- `Hr\TrainingProgram`
- `Hr\TrainingAttendance`
- `Hr\EmployeeSkill`
- `Hr\TrainingBond`

---

### STAGE 7: DISCIPLINE, GRIEVANCE & EXIT

#### 7.1 Database Schema

```sql
-- Disciplinary cases
CREATE TABLE hr_disciplinary_cases (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    employee_id BIGINT UNSIGNED NOT NULL,
    case_category VARCHAR(50) NOT NULL, -- 'misconduct', 'absenteeism', 'performance'
    incident_date DATE NOT NULL,
    reported_by BIGINT UNSIGNED,
    description TEXT,
    status VARCHAR(50) DEFAULT 'open', -- 'open', 'investigating', 'resolved', 'closed'
    outcome VARCHAR(50), -- 'verbal_warning', 'written_warning', 'suspension', 'termination'
    outcome_date DATE NULL,
    payroll_impact JSON, -- e.g., {'unpaid_suspension_days': 3}
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Grievances
CREATE TABLE hr_grievances (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    employee_id BIGINT UNSIGNED NOT NULL,
    complaint_type VARCHAR(50) NOT NULL,
    description TEXT,
    status VARCHAR(50) DEFAULT 'open', -- 'open', 'investigating', 'resolved', 'closed'
    assigned_to BIGINT UNSIGNED, -- HR case officer
    resolution TEXT,
    resolved_at TIMESTAMP NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Exit management
CREATE TABLE hr_exits (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    employee_id BIGINT UNSIGNED NOT NULL,
    exit_type VARCHAR(50) NOT NULL, -- 'resignation', 'termination', 'retirement', 'contract_expiry'
    effective_date DATE NOT NULL,
    notice_period_days INT,
    exit_reason TEXT,
    clearance_status VARCHAR(50) DEFAULT 'pending', -- 'pending', 'in_progress', 'completed'
    final_pay_status VARCHAR(50) DEFAULT 'pending', -- 'pending', 'calculated', 'approved', 'paid'
    final_pay_amount DECIMAL(15,2) NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Exit clearance checklist
CREATE TABLE hr_exit_clearance_items (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    exit_id BIGINT UNSIGNED NOT NULL,
    clearance_item VARCHAR(200) NOT NULL, -- 'laptop_returned', 'id_returned', 'access_removed'
    status VARCHAR(50) DEFAULT 'pending', -- 'pending', 'completed'
    completed_by BIGINT UNSIGNED,
    completed_at TIMESTAMP NULL,
    notes TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

#### 7.2 Models to Create

- `Hr\DisciplinaryCase`
- `Hr\Grievance`
- `Hr\Exit`
- `Hr\ExitClearanceItem`

---

### STAGE 8 & 9: REPORTING & ANALYTICS

#### 8.1 Key Reports to Implement

**Operational Reports:**
- Employee Register
- Attendance Register
- Leave Balance Report
- Payroll Register
- Overtime Analysis

**Statutory Reports:**
- PAYE Monthly Return
- NHIF Contribution Report
- Pension Schedules
- WCF & SDL Summaries

**Analytics:**
- Payroll Cost Trends
- Headcount Analysis
- Turnover Analysis
- Performance Distribution
- Training ROI

**Dashboards:**
- Executive Dashboard
- HR Dashboard
- Payroll Dashboard
- Manager Dashboard
- Employee Dashboard

---

## Technical Architecture

### Service Layer Pattern

Create service classes for complex business logic:

```
app/Services/Hr/
├── EmployeeService.php
├── AttendanceService.php
├── PayrollService.php
├── LeaveService.php
├── PerformanceService.php
├── TrainingService.php
└── ComplianceService.php
```

### Job Queue Integration

Use queues for:
- Payroll processing (already implemented)
- Leave accrual runs
- Attendance processing
- Notification sending
- Report generation

### API Endpoints

Create RESTful APIs for:
- ESS/MSS mobile apps
- Third-party integrations (biometric devices)
- Reporting dashboards

---

## Integration Points

### Existing ERP Modules

1. **Accounting/GL**
   - Payroll journal posting (already exists)
   - Leave liability accounting
   - Training cost allocation

2. **Inventory**
   - Asset return during exit clearance

3. **Banking**
   - Salary payment processing

4. **User Management**
   - Employee-user account linking
   - Role-based access control

---

## Testing Strategy

### Unit Tests
- Service layer methods
- Model relationships
- Calculation logic

### Integration Tests
- Payroll processing end-to-end
- Leave accrual workflows
- Approval workflows

### User Acceptance Testing
- HR workflows
- Payroll processing
- ESS/MSS user experience

---

## Deployment Plan

### Phase 1 Deployment (Weeks 1-6)
- Deploy Core HR enhancements
- Migrate existing employee data
- Train HR team

### Phase 2 Deployment (Weeks 7-10)
- Deploy Attendance system
- Integrate with payroll
- Train managers

### Phase 3 Deployment (Weeks 11-14)
- Deploy Payroll enhancements
- Test statutory compliance
- Train payroll team

### Phase 4 Deployment (Weeks 15-18)
- Deploy ESS/MSS
- User training
- Go-live support

### Phase 5-7 Deployment (Weeks 19-30)
- Sequential deployment
- Continuous training
- Feedback incorporation

---

## Next Steps

1. **Review & Approve Plan** - Stakeholder sign-off
2. **Resource Allocation** - Assign team members
3. **Detailed Design** - Create detailed technical specs for Phase 1
4. **Database Design** - Finalize all table structures
5. **Development Kickoff** - Begin Phase 1 implementation

---

## Success Metrics

- **Accuracy**: 100% payroll accuracy
- **Compliance**: Zero statutory violations
- **Efficiency**: 50% reduction in HR manual work
- **User Satisfaction**: 80%+ ESS/MSS adoption
- **Performance**: Dashboard loads < 2 seconds
- **Reliability**: 99.9% system uptime

---

**Document Status:** Updated - Phase 2 Complete, Phase 3 Mostly Complete, Phase 6 Added (Employment Lifecycle Management)  
**Last Updated:** January 2025  
**Next Review Date:** After Enhanced Statutory Engine and Payroll Reports completion

---

## Implementation Progress Summary

### ✅ Phase 1: Foundation & Core HR Enhancement
**Status:** Not Started (Foundation work may be needed)

### ✅ Phase 2: Time, Attendance & Leave Enhancement  
**Status:** **COMPLETE** (December 2024)
- All deliverables implemented and integrated

### ✅ Phase 3: Payroll Enhancement & Statutory Compliance
**Status:** **MOSTLY COMPLETE** (December 2024)
- Payroll Calendar: ✅ Complete
- Payment Approval Workflow: ✅ Complete
- Pay Groups: ✅ Complete
  - ✅ Integration into payroll processing (filter employees by pay group)
  - ✅ Pay group-based payroll runs (process specific pay groups)
  - ✅ Pay group calendar integration (use pay group cut-off/pay days)
  - ⚠️ Pay group-specific rules (different statutory rules per pay group) - Future enhancement
- Salary Structure Engine: ✅ Complete
  - ✅ Bulk assignment of salary structures
  - ✅ Salary structure templates
  - ✅ UI/UX improvements
  - ✅ Better validation and error handling
- Enhanced Statutory Engine: ⚠️ Partial (basic implementation exists, needs more comprehensive Tanzania-specific rules)
- Payroll Locking & Audit Controls: ✅ Complete
  - ✅ Payroll-level locking (prevent changes after processing)
  - ✅ Comprehensive audit log system
  - ✅ Reversal/correction workflow
  - ✅ Approval workflow audit trail
- Comprehensive Payroll Reports: ⚠️ Partial (index page with report cards created, individual reports pending)

### ❌ Phase 4-8: Not Started
- Phase 4: Employee & Manager Self-Service
- Phase 5: Performance & Training
- Phase 6: Employment Lifecycle Management (NEW)
  - Recruitment (Optional)
  - Onboarding
  - Confirmation
  - Transfers & Promotions
- Phase 7: Discipline, Grievance & Exit
- Phase 8: Reporting, Analytics & Dashboards

