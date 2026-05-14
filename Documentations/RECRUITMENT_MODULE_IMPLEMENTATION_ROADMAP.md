# RECRUITMENT & TALENT ACQUISITION MODULE
## Implementation Roadmap & Gap Analysis

**Date:** January 2025  
**Status:** Planning Phase  
**ERP Version:** SmartAccounting ERP

---

## EXECUTIVE SUMMARY

This document outlines the implementation plan for the Recruitment & Talent Acquisition Module based on the comprehensive blueprint. The system will be built in phases, prioritizing core functionality while ensuring compliance with Tanzania Employment Law, donor requirements, and PDP Act 2022.

---

## CURRENT STATE ASSESSMENT

### ✅ What Exists (Basic Implementation)

| Component | Status | Notes |
|-----------|--------|-------|
| Vacancy Requisitions | ✅ Basic | Has approval workflow, basic fields |
| Applicants | ✅ Basic | Basic personal info, status tracking |
| Interview Records | ✅ Basic | Simple interview tracking |
| Offer Letters | ✅ Basic | Basic offer management |
| Positions | ✅ Basic | Can be enhanced to Job Library |
| Approval Settings | ✅ Complete | Multi-level approval configured |

### ❌ What's Missing (Gap Analysis)

| Module | Priority | Complexity | Estimated Effort |
|--------|----------|------------|------------------|
| Job Library (Enhanced) | 🔴 High | Medium | 2-3 weeks |
| Eligibility Engine | 🔴 High | Medium | 1-2 weeks |
| Scoring & Ranking | 🔴 High | High | 2-3 weeks |
| Panel Interview Scoring | 🟡 Medium | Medium | 1-2 weeks |
| Talent Pool | 🟡 Medium | Low | 1 week |
| Public/Internal Portals | 🟡 Medium | High | 3-4 weeks |
| Enhanced Reporting | 🟢 Low | Medium | 1-2 weeks |
| Compliance Tracking | 🟢 Low | Low | 1 week |

---

## PHASED IMPLEMENTATION PLAN

### **PHASE 1: FOUNDATION (Weeks 1-4)**
**Goal:** Establish core master data and enhance existing requisition system

#### 1.1 Job Library Enhancement (Week 1-2)
**Transform Position → Job Library**

**Database Changes:**
```sql
-- Enhance hr_positions table
ALTER TABLE hr_positions ADD COLUMN IF NOT EXISTS:
  - job_code VARCHAR(50) UNIQUE
  - job_level VARCHAR(50)
  - reports_to_position_id BIGINT
  - contract_type ENUM('permanent', 'fixed_term', 'project', 'intern', 'volunteer', 'consultant')
  - contract_duration_months INT
  - salary_grade_id BIGINT
  - salary_range_min DECIMAL(15,2)
  - salary_range_max DECIMAL(15,2)
  - minimum_education_level VARCHAR(100)
  - required_certifications JSON
  - mandatory_skills JSON
  - optional_skills JSON
  - gender_equity_flag BOOLEAN DEFAULT FALSE
  - safeguarding_required BOOLEAN DEFAULT FALSE
  - project_grant_code VARCHAR(100)
  - version_number INT DEFAULT 1
  - effective_date DATE
  - is_active BOOLEAN DEFAULT TRUE
```

**New Models:**
- `Hr\JobLibrary` (enhanced Position model)
- `Hr\JobVersion` (for versioning)
- `Hr\JobSkill` (many-to-many with skills)

**Features:**
- ✅ Version control
- ✅ Skills management
- ✅ Certification requirements
- ✅ Donor/project linkage
- ✅ Gender equity tracking

#### 1.2 Enhanced Requisition (Week 2-3)
**Enhance VacancyRequisition model**

**New Fields:**
```sql
ALTER TABLE hr_vacancy_requisitions ADD COLUMN:
  - job_library_id BIGINT (link to Job Library)
  - hiring_justification TEXT
  - cost_center_id BIGINT
  - budget_line_id BIGINT
  - contract_period_months INT
  - recruitment_type ENUM('internal', 'external', 'both')
  - posting_start_date DATE
  - posting_end_date DATE
  - is_publicly_posted BOOLEAN DEFAULT FALSE
```

**Enhancements:**
- Link to Job Library (standardization)
- Budget validation
- Donor compliance checks
- Public posting flag

#### 1.3 Eligibility Engine (Week 3-4)
**Automated candidate filtering**

**New Table:**
```sql
CREATE TABLE hr_eligibility_rules (
  id BIGINT PRIMARY KEY,
  job_library_id BIGINT,
  rule_type ENUM('education', 'experience', 'certification', 'skill', 'safeguarding'),
  rule_operator ENUM('equals', 'greater_than', 'less_than', 'contains', 'in'),
  rule_value JSON,
  is_mandatory BOOLEAN DEFAULT TRUE,
  created_at TIMESTAMP,
  updated_at TIMESTAMP
);

CREATE TABLE hr_applicant_eligibility_checks (
  id BIGINT PRIMARY KEY,
  applicant_id BIGINT,
  rule_id BIGINT,
  passed BOOLEAN,
  reason TEXT,
  checked_at TIMESTAMP
);
```

**Service Class:**
- `Hr\Services\EligibilityService`
  - `checkApplicantEligibility($applicant, $job)`
  - `getEligibilityStatus($applicant)`
  - `getFailedRules($applicant)`

---

### **PHASE 2: INTELLIGENT SCREENING (Weeks 5-8)**
**Goal:** Automated scoring and ranking

#### 2.1 Scoring Engine (Week 5-6)
**Objective candidate evaluation**

**New Tables:**
```sql
CREATE TABLE hr_scoring_criteria (
  id BIGINT PRIMARY KEY,
  job_library_id BIGINT,
  criteria_name VARCHAR(100),
  criteria_type ENUM('education', 'experience', 'skills', 'certifications', 'additional'),
  weight_percentage DECIMAL(5,2),
  max_score DECIMAL(5,2),
  is_active BOOLEAN DEFAULT TRUE
);

CREATE TABLE hr_applicant_scores (
  id BIGINT PRIMARY KEY,
  applicant_id BIGINT,
  criteria_id BIGINT,
  score DECIMAL(5,2),
  scored_by BIGINT,
  scored_at TIMESTAMP,
  notes TEXT
);

CREATE TABLE hr_applicant_rankings (
  id BIGINT PRIMARY KEY,
  applicant_id BIGINT,
  vacancy_requisition_id BIGINT,
  final_score DECIMAL(5,2),
  rank INT,
  shortlist_status ENUM('not_shortlisted', 'shortlisted', 'reserve'),
  shortlisted_at TIMESTAMP,
  shortlisted_by BIGINT
);
```

**Service Class:**
- `Hr\Services\ScoringService`
  - `calculateScore($applicant, $job)`
  - `rankApplicants($vacancyRequisition)`
  - `getShortlist($vacancyRequisition, $threshold)`

**Formula:**
```
Final Score = Σ (Criteria Score × Weight Percentage)
Rank = Position in sorted list (descending by Final Score)
```

#### 2.2 Enhanced Interview Management (Week 7-8)
**Panel-based structured interviews**

**Enhance Interview Records:**
```sql
ALTER TABLE hr_interview_records ADD COLUMN:
  - panel_members JSON (array of user IDs)
  - interview_template_id BIGINT
  - competency_scores JSON
  - panel_scores JSON (individual panel member scores)
  - consolidated_score DECIMAL(5,2)
  - is_panel_complete BOOLEAN DEFAULT FALSE
```

**New Tables:**
```sql
CREATE TABLE hr_interview_templates (
  id BIGINT PRIMARY KEY,
  job_library_id BIGINT,
  template_name VARCHAR(200),
  competencies JSON,
  questions JSON,
  scoring_guide JSON
);

CREATE TABLE hr_panel_scores (
  id BIGINT PRIMARY KEY,
  interview_record_id BIGINT,
  panel_member_id BIGINT,
  competency_scores JSON,
  overall_score DECIMAL(5,2),
  comments TEXT,
  submitted_at TIMESTAMP,
  UNIQUE(interview_record_id, panel_member_id)
);
```

**Features:**
- ✅ Panel members cannot see others' scores before submission
- ✅ Mandatory comments for outliers
- ✅ Automatic consolidation
- ✅ Final ranking

---

### **PHASE 3: TALENT MANAGEMENT (Weeks 9-11)**
**Goal:** Talent pool and internal mobility

#### 3.1 Talent Pool (Week 9-10)
**Reusable candidate database**

**New Table:**
```sql
CREATE TABLE hr_talent_pool (
  id BIGINT PRIMARY KEY,
  applicant_id BIGINT,
  source_type ENUM('past_applicant', 'intern', 'volunteer', 'recommended', 'rejected_but_qualified'),
  source_reference_id BIGINT,
  skills JSON,
  experience_summary TEXT,
  availability_status ENUM('available', 'not_available', 'interested'),
  tags JSON,
  added_to_pool_at TIMESTAMP,
  added_by BIGINT
);
```

**Features:**
- ✅ Auto-add rejected but qualified candidates
- ✅ Manual addition from past applicants
- ✅ Search and filter by skills/experience
- ✅ Link to internal recruitment

#### 3.2 Enhanced Offer Management (Week 10-11)
**Structured offer workflow**

**Enhance Offer Letters:**
```sql
ALTER TABLE hr_offer_letters ADD COLUMN:
  - offer_type ENUM('fixed_term', 'project_based', 'intern', 'volunteer', 'consultant')
  - salary_band_validation BOOLEAN DEFAULT FALSE
  - offer_approval_level INT
  - digital_signature_required BOOLEAN DEFAULT TRUE
  - contract_template_id BIGINT
  - conditions JSON
  - policies_acknowledged JSON
```

**New Tables:**
```sql
CREATE TABLE hr_contract_templates (
  id BIGINT PRIMARY KEY,
  contract_type VARCHAR(50),
  template_content TEXT,
  variables JSON,
  is_active BOOLEAN DEFAULT TRUE
);

CREATE TABLE hr_offer_approvals (
  id BIGINT PRIMARY KEY,
  offer_letter_id BIGINT,
  approval_level INT,
  approver_id BIGINT,
  status ENUM('pending', 'approved', 'rejected'),
  comments TEXT,
  approved_at TIMESTAMP
);
```

---

### **PHASE 4: PORTALS & INTEGRATION (Weeks 12-15)**
**Goal:** Public access and HR integration

#### 4.1 Public/Internal Portals (Week 12-14)
**Candidate-facing interfaces**

**New Routes:**
- `/recruitment/public/jobs` - Public job listings
- `/recruitment/public/apply/{job}` - Application form
- `/recruitment/internal/jobs` - Internal opportunities
- `/recruitment/internal/apply/{job}` - Internal application

**Features:**
- ✅ Responsive design
- ✅ Application form with file uploads
- ✅ PDP Act consent tracking
- ✅ No religion/tribe/political fields
- ✅ Disability optional (equal opportunity)

**New Table:**
```sql
CREATE TABLE hr_job_postings (
  id BIGINT PRIMARY KEY,
  vacancy_requisition_id BIGINT,
  posting_type ENUM('public', 'internal', 'both'),
  posting_start_date DATE,
  posting_end_date DATE,
  is_active BOOLEAN DEFAULT TRUE,
  view_count INT DEFAULT 0,
  application_count INT DEFAULT 0
);
```

#### 4.2 Onboarding Integration (Week 14-15)
**Seamless candidate → employee transition**

**Service Class:**
- `Hr\Services\OnboardingService`
  - `convertApplicantToEmployee($applicant, $offerLetter)`
  - `autoPopulateEmployeeData($employee, $applicant)`
  - `createInitialRecords($employee)`

**Auto-population:**
- Personal data from application
- Position & grade from offer
- Salary from offer
- Contract details
- Statutory flags

---

### **PHASE 5: COMPLIANCE & REPORTING (Weeks 16-18)**
**Goal:** Audit readiness and analytics

#### 5.1 Compliance Tracking (Week 16)
**Legal and donor compliance**

**New Tables:**
```sql
CREATE TABLE hr_compliance_checks (
  id BIGINT PRIMARY KEY,
  vacancy_requisition_id BIGINT,
  compliance_type ENUM('employment_law', 'donor', 'safeguarding', 'gender_equity'),
  check_status ENUM('passed', 'failed', 'warning'),
  check_details JSON,
  checked_at TIMESTAMP,
  checked_by BIGINT
);

CREATE TABLE hr_consent_tracking (
  id BIGINT PRIMARY KEY,
  applicant_id BIGINT,
  consent_type ENUM('pdp_act', 'data_processing', 'background_check'),
  consent_given BOOLEAN,
  consent_date TIMESTAMP,
  ip_address VARCHAR(45),
  user_agent TEXT
);
```

#### 5.2 Reporting & Analytics (Week 17-18)
**Standard reports and dashboards**

**Reports:**
1. **Recruitment Pipeline**
   - Applications by stage
   - Time in each stage
   - Conversion rates

2. **Time to Hire**
   - Average days per stage
   - Bottleneck identification

3. **Gender Distribution**
   - Applications by gender
   - Hires by gender
   - Donor compliance

4. **Source Effectiveness**
   - Applications by source
   - Hire rate by source
   - Cost per hire

5. **Compliance Checklist**
   - Missing documentation
   - Expired consents
   - Policy acknowledgements

**New Controller:**
- `Hr\RecruitmentReportsController`

---

## TECHNICAL ARCHITECTURE

### Database Design Principles
- ✅ Audit trail on all tables (`created_by`, `updated_by`, `timestamps`)
- ✅ Soft deletes where appropriate
- ✅ JSON fields for flexible data (skills, competencies)
- ✅ Indexes on foreign keys and status fields
- ✅ Unique constraints on business keys

### Service Layer Pattern
```
Services/
  ├── EligibilityService.php
  ├── ScoringService.php
  ├── RankingService.php
  ├── InterviewService.php
  ├── OfferService.php
  ├── OnboardingService.php
  └── ComplianceService.php
```

### Security & Privacy
- ✅ Role-based access control
- ✅ Encryption at rest (sensitive data)
- ✅ HTTPS for all portals
- ✅ Consent tracking (PDP Act)
- ✅ Data retention policies
- ✅ Audit logs

---

## CONFIGURATION & SETTINGS

### System Configuration
```php
// config/recruitment.php
return [
    'scoring' => [
        'default_weights' => [
            'education' => 25,
            'experience' => 30,
            'skills' => 25,
            'certifications' => 10,
            'additional' => 10,
        ],
        'shortlist_threshold' => 70, // percentage
    ],
    'eligibility' => [
        'auto_check' => true,
        'strict_mode' => false, // Allow exceptions
    ],
    'interview' => [
        'panel_min_members' => 2,
        'require_all_scores' => true,
    ],
    'compliance' => [
        'require_pdp_consent' => true,
        'retention_days' => 365,
    ],
];
```

---

## TESTING STRATEGY

### Unit Tests
- Eligibility rules engine
- Scoring calculations
- Ranking algorithms
- Compliance checks

### Integration Tests
- End-to-end recruitment flow
- Approval workflows
- Onboarding integration
- Portal submissions

### User Acceptance Tests
- HR Admin workflows
- Hiring manager workflows
- Candidate experience
- Reporting accuracy

---

## MIGRATION STRATEGY

### Data Migration
1. **Existing Positions → Job Library**
   - Map existing position data
   - Create version 1 for all
   - Set effective dates

2. **Existing Applicants**
   - Preserve all data
   - Add eligibility status (retroactive check)
   - Calculate initial scores if possible

3. **Existing Interviews**
   - Migrate to new structure
   - Create panel records if multiple interviewers

---

## SUCCESS METRICS

### Key Performance Indicators
- ✅ Time to hire (target: <30 days)
- ✅ Application to hire ratio
- ✅ Candidate satisfaction score
- ✅ Compliance audit pass rate
- ✅ System adoption rate

---

## RISK MITIGATION

| Risk | Impact | Mitigation |
|------|--------|------------|
| Data migration issues | High | Phased migration, backup strategy |
| User adoption | Medium | Training, documentation, support |
| Performance (large datasets) | Medium | Indexing, pagination, caching |
| Compliance gaps | High | Regular audits, legal review |

---

## NEXT STEPS

### Immediate Actions (Week 1)
1. ✅ Review and approve this roadmap
2. ✅ Set up development environment
3. ✅ Create database migration scripts (Phase 1)
4. ✅ Begin Job Library enhancement

### Communication Plan
- Weekly progress updates
- Stakeholder demos at end of each phase
- User training sessions
- Documentation updates

---

## APPENDIX

### A. Tanzania Employment Law Compliance
- ✅ No discrimination fields (religion, tribe, politics)
- ✅ Equal opportunity reporting
- ✅ Contract compliance
- ✅ Statutory deductions

### B. Donor Compliance Requirements
- ✅ Gender equity tracking
- ✅ Project/grant linkage
- ✅ Budget validation
- ✅ Reporting capabilities

### C. PDP Act 2022 Compliance
- ✅ Consent tracking
- ✅ Data minimization
- ✅ Right to deletion
- ✅ Security measures

---

**Document Version:** 1.0  
**Last Updated:** January 2025  
**Owner:** Development Team  
**Status:** Ready for Implementation
