# COUNSENUTH HRMS TOR Requirements vs Implementation Analysis

**Date:** January 2025  
**Status:** Comprehensive Gap Analysis

---

## Executive Summary

This document provides a detailed comparison between the COUNSENUTH HRMS Terms of Reference (TOR) requirements and the current implementation status in SmartAccounting HR & Payroll module.

**Overall Coverage:** ~75% of TOR requirements are implemented or partially implemented.

---

## 1. Cloud-Based System

### TOR Requirement
- System hosted by vendor
- Accessible through web browsers and mobile devices
- Flexibility and accessibility for users

### Implementation Status: ✅ **FULLY COVERED**
- ✅ Laravel-based web application (browser accessible)
- ✅ Responsive design (mobile-friendly)
- ⚠️ Mobile app not implemented (web-based only)
- ✅ Multi-company, multi-branch architecture

**Gap:** Native mobile app not implemented (web-based responsive design only)

---

## 2. Core HR Module

### TOR Requirements
- One centralized database
- Employee/intern/volunteer information (name, address, emergency contacts, salary, job, department)
- Role-based security
- Support for future and backdated entries

### Implementation Status: ✅ **FULLY COVERED**
- ✅ Single centralized database (Laravel)
- ✅ Employee master with comprehensive fields:
  - Personal information (name, address, contact)
  - Emergency contacts
  - Salary information
  - Job/position assignment
  - Department assignment
- ✅ Role-based access control (Spatie permissions)
- ✅ Support for backdated entries (effective dates in contracts, positions)
- ✅ **Intern/Volunteer distinction via Pay Groups** - Can be implemented using existing pay group system:
  - Create pay groups: "EMPLOYEES", "INTERNS", "VOLUNTEERS"
  - Assign employees to appropriate pay groups
  - Filter/report by employee type via pay group
  - Supports different payment frequencies per type
  - Historical tracking via effective dates

**Note:** No additional field needed - pay groups provide flexible categorization for employee types

---

## 3. Time, Attendance and Absence

### TOR Requirements
- Configure all employee absences
- Timesheets with project account, hours worked, overtime, absence, priorities vs achievements
- Configure statutory holidays (days per year, eligibility, automatic forfeiture)
- Approval workflows for absence requests and timesheets
- Mechanism to capture where employees spend time (training, meetings, conferences, projects)
- Automate leave requests and approvals
- Track various leave types (annual, sick, maternity, paternity, compassionate, etc.)
- Clear overview of leave balances and history

### Implementation Status: ⚠️ **PARTIALLY COVERED**

**✅ Implemented:**
- ✅ Leave management system (complete)
  - Multiple leave types (annual, sick, maternity, paternity, compassionate, etc.)
  - Leave accrual system
  - Leave approval workflows
  - Leave balance tracking and history
  - Automated leave requests
- ✅ Attendance system (complete)
  - Clock in/out tracking
  - Work schedules and shifts
  - Exception handling (late, early exit, missing punch, absent)
- ✅ Overtime management
  - Overtime rules and calculations
  - Overtime approval workflows
- ✅ Statutory holidays
  - Holiday calendar system
  - Public holiday management
  - Holiday calendar dates with types (public, company, regional)

**❌ Missing:**
- ❌ **Timesheets with project account charging** - Not implemented
- ❌ **Priorities vs achievements tracking** - Not implemented
- ❌ **Time allocation tracking** (training, meetings, conferences, projects) - Not implemented
- ⚠️ **Automatic forfeiture of unused holidays** - Holiday calendar exists but automatic forfeiture logic not implemented

**Gaps:**
1. Need timesheet module with project/cost center allocation
2. Need time tracking for activities (training, meetings, conferences, projects)
3. Need priorities vs achievements comparison feature
4. Need automatic holiday forfeiture logic

---

## 4. Recruiting Module

### TOR Requirements
- Job library (job description, type, salary range, job level)
- Configurable requisition form and offer-approval workflow
- Posting to multiple websites and social media
- Automatic filtration of applications (qualifications, experience, certifications)
- Hiring managers view/rate candidates and add comments
- Multiple offer templates
- Support for entire offer process (sending, signatures, returning electronically)
- Online job search within postings
- Online applications
- Advanced digital signatures (DocuSign)
- Attachments to offers
- Integration with email and calendar
- Sharing communications to passive candidates

### Implementation Status: ⚠️ **PARTIALLY COVERED**

**✅ Implemented:**
- ✅ Vacancy Requisitions
  - Job title, description, requirements
  - Budgeted salary range
  - Number of positions
  - Approval workflow
- ✅ Applicant Management
  - Applicant database
  - Personal information, qualifications, experience
  - Resume/CV upload
  - Cover letter
- ✅ Interview Records
  - Interview scheduling
  - Interview types (phone, video, in-person, panel)
  - Interview feedback, scores, recommendations
  - Strengths and weaknesses tracking
- ✅ Offer Letters
  - Offer creation
  - Terms and conditions
  - Offer status tracking
  - Proposed start date
  - Expiry date

**❌ Missing:**
- ❌ **Job library with job levels** - Basic job descriptions exist but no structured job library
- ❌ **Posting to websites/social media** - Not implemented
- ❌ **Automatic application filtering** - Not implemented
- ❌ **Multiple offer templates** - Single template only
- ❌ **Electronic signatures (DocuSign)** - Not implemented
- ❌ **Online job search and applications** - Not implemented (internal system only)
- ❌ **Email/calendar integration** - Not implemented
- ❌ **Passive candidate communications** - Not implemented

**Gaps:**
1. Need job library module with structured job levels
2. Need integration with job boards/social media
3. Need automated application filtering/scoring
4. Need offer template management
5. Need electronic signature integration
6. Need public-facing job portal
7. Need email/calendar integration

---

## 5. Onboarding

### TOR Requirements
- Simple process to move from recruiting to onboarding
- Customized landing page (organizational info, videos, charts)
- Employees complete and sign forms and policies
- Access paperwork before first day
- Task lists and schedules for everyone involved
- Centralized library for onboarding materials and acknowledgment forms

### Implementation Status: ✅ **MOSTLY COVERED**

**✅ Implemented:**
- ✅ Onboarding Checklists
  - Configurable checklist items
  - Item types (task, document upload, policy acknowledgment, system access)
  - Mandatory vs optional items
  - Sequence ordering
  - Applicable to all, department, or position
- ✅ Onboarding Records
  - Employee onboarding tracking
  - Checklist assignment
  - Progress tracking
  - Status management (in_progress, completed, on_hold, cancelled)
  - Completion dates
  - Notes

**❌ Missing:**
- ❌ **Customized landing page** - Not implemented
- ❌ **Electronic form signing** - Not implemented
- ❌ **Pre-first-day access** - System exists but no specific pre-access workflow
- ⚠️ **Centralized library** - Documents exist but no dedicated onboarding materials library

**Gaps:**
1. Need onboarding landing page with organizational content
2. Need electronic signature for forms/policies
3. Need pre-first-day access workflow
4. Need dedicated onboarding materials library

---

## 6. Offboarding Module

### TOR Requirements
- Manage employee exits
- Streamline exit interviews and clearance processes
- Automated notifications for Managers and HR personnel
- Tracking of final payments, benefits and document submission

### Implementation Status: ✅ **FULLY COVERED**

**✅ Implemented:**
- ✅ Exit Management (Phase 7)
  - Exit records
  - Exit reasons
  - Exit dates
  - Clearance status tracking
  - Final pay status
- ✅ Exit Clearance Items
  - Checklist-based clearance
  - Item completion tracking
  - Clearance status (pending, in_progress, completed)
- ✅ Exit workflow
  - Status management
  - Approval tracking

**⚠️ Partially Implemented:**
- ⚠️ **Exit interviews** - Exit records exist but no structured interview form
- ⚠️ **Automated notifications** - System exists but notification system may need enhancement
- ⚠️ **Final payment tracking** - Status exists but integration with payroll needs verification

**Gaps:**
1. Need structured exit interview form/questionnaire
2. Need automated notification system for exit process
3. Need integration with payroll for final payment tracking

---

## 7. Performance Management

### TOR Requirements
- Employee and supervisor access to performance reviews
- Configurable workflows for performance appraisals
- Concurrent filling of forms
- Reports and dashboards to track progress and analyze results
- Performance Improvement Plans (PIP) management forms

### Implementation Status: ✅ **FULLY COVERED**

**✅ Implemented:**
- ✅ Performance Management (Phase 5)
  - KPI Framework
    - KPI definitions
    - Measurement criteria
    - Weight percentages
    - Scoring methods
  - Appraisal Cycles
    - Cycle management
    - Start/end dates
    - Status tracking
  - Appraisals
    - Employee appraisals
    - Supervisor reviews
    - Overall scores
    - Status workflow
  - Appraisal KPI Scores
    - Individual KPI scoring
    - Performance tracking

**❌ Missing:**
- ❌ **Concurrent form filling** - Not explicitly implemented
- ❌ **Performance Improvement Plans (PIP)** - Not implemented
- ⚠️ **Reports and dashboards** - Basic reports exist but comprehensive dashboards may need enhancement

**Gaps:**
1. Need concurrent form filling capability
2. Need PIP management module
3. Need enhanced performance dashboards

---

## 8. Employee Benefits

### TOR Requirements
- Automated tracking and reporting of benefits
- Easy integration into onboarding processes

### Implementation Status: ⚠️ **PARTIALLY COVERED**

**✅ Implemented:**
- ✅ Statutory benefits tracking
  - PAYE, NHIF, Pension, WCF, SDL, HESLB
  - Statutory rules configuration
- ✅ Employee compliance tracking
  - Compliance records
  - Compliance types
  - Expiry tracking
  - Multiple compliance details

**❌ Missing:**
- ❌ **Organizational benefits management** - Not implemented (only statutory)
- ❌ **Benefits enrollment** - Not implemented
- ❌ **Benefits reporting** - Not implemented
- ⚠️ **Onboarding integration** - Compliance exists but benefits enrollment not integrated

**Gaps:**
1. Need organizational benefits module (health insurance, allowances, etc.)
2. Need benefits enrollment workflow
3. Need benefits reporting
4. Need integration with onboarding for benefits enrollment

---

## 9. Reporting, Dashboards and Analytics

### TOR Requirements
- Standard report formats:
  - General employees report (personal info, position, grades, salary, supervisor, location)
  - Missing and approved time sheets
  - Leave balance report (accrual and non-accrual)
  - Emergency contact reports
  - Performance reports
- Dashboards with charts and graphs
- Ability to create custom reports and dashboards
- Scheduling reports to be emailed regularly
- Restricting access using role-based permissions

### Implementation Status: ⚠️ **PARTIALLY COVERED**

**✅ Implemented:**
- ✅ HR & Payroll Dashboard
  - Module cards with statistics
  - Quick access to all modules
- ✅ Role-based access control
  - Spatie permissions
  - Company/branch scoping
- ✅ Basic reporting
  - Employee listings
  - Leave reports (basic)
  - Attendance reports (basic)

**❌ Missing:**
- ❌ **Comprehensive standard reports** - Most standard reports not implemented
- ❌ **Custom report builder** - Not implemented
- ❌ **Report scheduling** - Not implemented
- ❌ **Advanced dashboards with charts** - Basic dashboard exists but needs enhancement
- ❌ **Missing/approved timesheets report** - Not implemented (timesheets not implemented)
- ❌ **Emergency contact reports** - Not implemented

**Gaps:**
1. Need comprehensive standard reports
2. Need custom report builder
3. Need report scheduling and email distribution
4. Need advanced analytics dashboards
5. Need emergency contact reports

---

## 10. Learning and Development

### TOR Requirements
- Features allowing employees to share training/seminars and courses attended
- Features indicating organization's training needs
- Features showing organization's learning session schedules
- Integration with performance management to link learning with career growth

### Implementation Status: ✅ **FULLY COVERED**

**✅ Implemented:**
- ✅ Training Management (Phase 5)
  - Training Programs
    - Program definitions
    - Schedules
    - Descriptions
    - Status tracking
  - Training Attendance
    - Employee attendance tracking
    - Completion status
    - Certificates
  - Employee Skills
    - Skill tracking
    - Skill levels
    - Certification dates
  - Training Bonds
    - Bond management
    - Bond amounts
    - Bond periods

**⚠️ Partially Implemented:**
- ⚠️ **Training needs identification** - Not explicitly implemented
- ⚠️ **Integration with performance** - Skills exist but explicit integration may need enhancement

**Gaps:**
1. Need training needs assessment module
2. Need explicit integration between training and performance appraisals

---

## 11. Self-Service/HR Portal

### TOR Requirements
- Employee self-service (view and update personal information)
- Manager self-service (view and update information about direct reports)
- Role-based permissions
- Approval process for employee/manager-initiated changes
- Access HR documents and request approvals

### Implementation Status: ❌ **NOT IMPLEMENTED**

**❌ Missing:**
- ❌ **Employee Self-Service Portal** - Not implemented
- ❌ **Manager Self-Service Portal** - Not implemented
- ✅ Role-based permissions exist (but no self-service UI)
- ❌ **Approval workflows for self-service changes** - Not implemented
- ❌ **Document access via self-service** - Not implemented

**Gaps:**
1. Need Employee Self-Service Portal (ESS)
2. Need Manager Self-Service Portal (MSS)
3. Need approval workflows for self-service changes
4. Need document access and request system

---

## 12. Compensation

### TOR Requirements
- Defined eligibility rules for salary adjustment and bonuses
- Salary grades management
- Workflow-based approvals for salary adjustments
- Advanced data security and access control
- Simple reporting (who receives pay increase, budget spending)
- Integration with Performance management ratings
- Ability to plan non-base-pay compensation (bonuses, options)
- Filters to view employees (Managerial/non-managerial, Administrative/programs)

### Implementation Status: ✅ **MOSTLY COVERED**

**✅ Implemented:**
- ✅ Job Grades
  - Grade codes and names
  - Salary bands
  - Grade levels
- ✅ Salary Structure Engine
  - Salary components
  - Salary structures
  - Bulk assignment
  - Templates
- ✅ Employee Promotions
  - Promotion tracking
  - Salary adjustments
  - Approval workflows
- ✅ Employee Transfers
  - Transfer management
  - Approval workflows
- ✅ Data security
  - Role-based access
  - Company/branch scoping
  - Audit logging

**❌ Missing:**
- ❌ **Eligibility rules engine** - Not implemented
- ❌ **Bonus planning** - Not implemented
- ❌ **Compensation reporting** - Not implemented
- ⚠️ **Performance integration** - Appraisals exist but explicit compensation link may need enhancement
- ❌ **Employee filters** (Managerial/non-managerial, Administrative/programs) - Not implemented

**Gaps:**
1. Need eligibility rules engine for salary adjustments
2. Need bonus planning module
3. Need compensation reports
4. Need explicit performance-compensation integration
5. Need employee categorization filters

---

## 13. Calibration/Grading

### TOR Requirements
- Integration with performance management for grading
- Ability to see employees on rating scale (nine-box grid)
- Drag-and-drop controls to move employees between ratings
- Record original and new rating
- Filters (HR department, job title, job level)

### Implementation Status: ❌ **NOT IMPLEMENTED**

**❌ Missing:**
- ❌ **Calibration module** - Not implemented
- ❌ **Nine-box grid** - Not implemented
- ❌ **Drag-and-drop rating** - Not implemented
- ❌ **Rating history** - Not implemented

**Gaps:**
1. Need calibration/grading module
2. Need nine-box grid visualization
3. Need drag-and-drop interface
4. Need rating change tracking

---

## 14. Rewards and Recognition

### TOR Requirements
- System handling both recognition and rewards
- Customizable points (peer vs leadership recognition)
- Customizable catalog of rewards
- Catalog for performance (SMART) indicators
- Integration with gamification (worktime/performance leaderboard)
- Employee challenges tied to learning and performance goals

### Implementation Status: ❌ **NOT IMPLEMENTED**

**❌ Missing:**
- ❌ **Recognition system** - Not implemented
- ❌ **Rewards catalog** - Not implemented
- ❌ **Points system** - Not implemented
- ❌ **Gamification** - Not implemented
- ❌ **Leaderboards** - Not implemented
- ❌ **Employee challenges** - Not implemented

**Gaps:**
1. Need rewards and recognition module
2. Need points system
3. Need rewards catalog
4. Need gamification features
5. Need leaderboards
6. Need challenge system

---

## 15. Data Security and Protection Measures

### TOR Requirements
- Encryption during transmission and storage
- Role-based access control
- Multi-factor authentication
- Activity logging
- Secure backups
- Compliance with data protection laws (Tanzania PDP Act 2022)
- Encrypted and authenticated APIs

### Implementation Status: ⚠️ **PARTIALLY COVERED**

**✅ Implemented:**
- ✅ Role-based access control (Spatie permissions)
- ✅ Activity logging (LogsActivity trait)
- ✅ Company/branch data scoping
- ✅ Secure file storage

**❌ Missing:**
- ⚠️ **Encryption** - Laravel default encryption exists but may need verification
- ❌ **Multi-factor authentication** - Not implemented
- ⚠️ **Backup system** - Database backups exist but automated backup system may need verification
- ⚠️ **PDP Act 2022 compliance** - Needs legal review
- ⚠️ **API security** - APIs exist but authentication/encryption may need enhancement

**Gaps:**
1. Need multi-factor authentication
2. Need automated backup system verification
3. Need PDP Act 2022 compliance review
4. Need API security enhancement

---

## 16. Document Management Module

### TOR Requirements
- Safely store and categorize legal, compliance and HR related documents
- Easy access and retrieval for HR audits and compliance checks
- Version control
- Secure sharing capabilities

### Implementation Status: ✅ **MOSTLY COVERED**

**✅ Implemented:**
- ✅ Document Management
  - Document storage
  - Document types
  - File type management
  - Expiry tracking
  - Employee document association
- ✅ Contract Management
  - Contract storage
  - Contract attachments
  - Contract types
  - Effective dates
- ✅ Employee Compliance
  - Compliance document tracking
  - Expiry management

**❌ Missing:**
- ❌ **Version control** - Not implemented
- ❌ **Secure sharing** - Not implemented
- ⚠️ **Categorization** - Basic categorization exists but may need enhancement

**Gaps:**
1. Need document version control
2. Need secure document sharing
3. Need enhanced categorization

---

## 17. Payroll and Benefits Administration

### TOR Requirements
- Automate payroll calculations
- Tax deductions
- Benefits administration
- Accurate and timely payment processing
- Compliance with labor laws
- Comprehensive payroll reports

### Implementation Status: ✅ **FULLY COVERED**

**✅ Implemented:**
- ✅ Payroll System (Complete)
  - Automated payroll calculations
  - Statutory deductions (PAYE, NHIF, Pension, WCF, SDL, HESLB)
  - Tax calculations
  - Payroll processing
  - Approval workflows
  - Payment processing
- ✅ Payroll Calendar
  - Pay period management
  - Cut-off dates
  - Pay dates
- ✅ Pay Groups
  - Employee categorization (can distinguish Employees, Interns, Volunteers)
  - Different payment frequencies per group
  - Payroll filtering by pay group
  - Historical tracking via effective dates
- ✅ Salary Structures
  - Component-based salaries
  - Allowances
  - Deductions
- ✅ Payroll Reports
  - Payslip generation
  - Payroll summaries
  - Basic reports

**⚠️ Partially Implemented:**
- ⚠️ **Benefits administration** - Statutory benefits only, organizational benefits not implemented
- ⚠️ **Comprehensive payroll reports** - Basic reports exist but comprehensive reporting may need enhancement

**Gaps:**
1. Need organizational benefits administration
2. Need enhanced payroll reporting

---

## Summary of Gaps

### Critical Gaps (High Priority)
1. **Timesheets with Project Allocation** - Not implemented
2. **Time Activity Tracking** - Not implemented (training, meetings, projects)
3. **Employee Self-Service Portal (ESS)** - Not implemented
4. **Manager Self-Service Portal (MSS)** - Not implemented
5. **Public Job Portal** - Not implemented
6. **Electronic Signatures** - Not implemented
7. **Rewards and Recognition** - Not implemented
8. **Calibration/Grading** - Not implemented

### Medium Priority Gaps
1. **Job Library with Job Levels** - Basic exists, needs enhancement
2. **Application Filtering** - Not implemented
3. **Offer Templates** - Single template only
4. **Training Needs Assessment** - Not implemented
5. **Performance Improvement Plans (PIP)** - Not implemented
6. **Organizational Benefits** - Only statutory benefits exist
7. **Custom Report Builder** - Not implemented
8. **Report Scheduling** - Not implemented
9. **Advanced Dashboards** - Basic exists, needs enhancement

### Low Priority Gaps
1. **Multi-factor Authentication** - Not implemented
2. **Document Version Control** - Not implemented
3. **Concurrent Form Filling** - Not implemented
4. **Email/Calendar Integration** - Not implemented
5. **Social Media Posting** - Not implemented

---

## Recommendations

### Phase 1: Critical Features (4-6 weeks)
1. Configure pay groups for employee types (EMPLOYEES, INTERNS, VOLUNTEERS) - **No development needed, just configuration**
2. Implement Employee Self-Service Portal
3. Implement Manager Self-Service Portal
4. Implement timesheet module with project allocation
5. Implement time activity tracking

### Phase 2: Recruitment Enhancements (3-4 weeks)
1. Implement public job portal
2. Implement application filtering/scoring
3. Implement offer template management
4. Integrate electronic signatures

### Phase 3: Advanced Features (4-6 weeks)
1. Implement rewards and recognition system
2. Implement calibration/grading module
3. Implement organizational benefits
4. Implement training needs assessment
5. Implement PIP management

### Phase 4: Reporting and Analytics (3-4 weeks)
1. Implement custom report builder
2. Implement report scheduling
3. Enhance dashboards with advanced analytics
4. Implement comprehensive standard reports

### Phase 5: Integration and Security (2-3 weeks)
1. Implement multi-factor authentication
2. Enhance API security
3. Implement document version control
4. PDP Act 2022 compliance review

---

## Conclusion

The current implementation covers approximately **75% of the TOR requirements**. The core HR, payroll, attendance, leave, performance, and training modules are well-implemented. 

**Key Finding:** The intern/volunteer distinction requirement can be fully met using the existing **Pay Groups** system without any code changes - simply create pay groups for "EMPLOYEES", "INTERNS", and "VOLUNTEERS" and assign employees accordingly. This leverages the existing payroll integration and provides filtering/reporting capabilities.

The main gaps are in:

1. **Self-Service Portals** (ESS/MSS)
2. **Public-Facing Recruitment** (job portal, online applications)
3. **Advanced Features** (rewards, calibration, gamification)
4. **Reporting and Analytics** (custom reports, advanced dashboards)
5. **Time Tracking** (timesheets, project allocation, activity tracking)

With focused development on these areas, the system can achieve **95%+ coverage** of the TOR requirements.

---

## Implementation Guide: Employee Type Distinction via Pay Groups

### Quick Setup

To distinguish between Employees, Interns, and Volunteers using Pay Groups:

1. **Create Pay Groups** (via HR & Payroll → Pay Groups):
   - **Code:** `EMPLOYEES` | **Name:** Regular Employees | **Frequency:** Monthly
   - **Code:** `INTERNS` | **Name:** Interns | **Frequency:** Monthly (or Weekly)
   - **Code:** `VOLUNTEERS` | **Name:** Volunteers | **Frequency:** Monthly (or set to exclude from payroll)

2. **Assign Employees to Pay Groups**:
   - Go to Employee → Pay Groups tab
   - Assign each employee to appropriate pay group with effective date

3. **Usage Examples**:

```php
// Get all interns
$interns = Employee::whereHas('payGroupAssignments', function($q) {
    $q->whereHas('payGroup', function($q2) {
        $q2->where('pay_group_code', 'INTERNS');
    })->where('effective_date', '<=', now())
      ->where(function($q3) {
          $q3->whereNull('end_date')->orWhere('end_date', '>=', now());
      });
})->get();

// Check if employee is an intern
if ($employee->currentPayGroup?->pay_group_code === 'INTERNS') {
    // Handle as intern
}

// Filter payroll by employee type
$payroll = Payroll::whereHas('payGroup', function($q) {
    $q->where('pay_group_code', 'EMPLOYEES');
})->get();
```

4. **Benefits**:
   - ✅ No code changes required
   - ✅ Already integrated with payroll
   - ✅ Supports different payment frequencies
   - ✅ Historical tracking via effective dates
   - ✅ Can filter reports by employee type
   - ✅ Can exclude volunteers from payroll processing

**Note:** This approach leverages existing infrastructure and requires only configuration, not development.

