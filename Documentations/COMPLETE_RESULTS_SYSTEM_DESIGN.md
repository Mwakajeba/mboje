# COMPLETE UNIVERSITY RESULTS MANAGEMENT SYSTEM
## Database Design Document

---

## 📋 SYSTEM OVERVIEW

This system manages:
- ✅ Course Registrations
- ✅ Continuous Assessments (40%)
- ✅ Final Exams (60%)
- ✅ Results Calculation
- ✅ GPA Calculation (Semester + Cumulative)
- ✅ Transcripts Generation
- ✅ Course Retakes/Attempts

---

## 🗂️ DATABASE TABLES (Complete Structure)

### 1️⃣ ACADEMIC YEAR & SEMESTER (Foundation)

```sql
-- Already exists: college_academic_years
id, name, start_date, end_date, status, created_at, updated_at

-- Already exists: college_semesters  
id, name, number, description, status, created_at, updated_at
```

---

### 2️⃣ GRADING SCALE (Configurable per University)

```sql
TABLE: grading_scales
id                  INT PRIMARY KEY
name                VARCHAR          -- e.g., "Undergraduate Scale"
description         TEXT
is_active           BOOLEAN
company_id          INT FK
branch_id           INT FK
created_at          TIMESTAMP
updated_at          TIMESTAMP
```

```sql
TABLE: grading_scale_items
id                  INT PRIMARY KEY
grading_scale_id    INT FK
min_marks           INT              -- 80
max_marks           INT              -- 100
grade               VARCHAR          -- A
remark              VARCHAR          -- Excellent
gpa_points          DECIMAL(3,2)     -- 5.00
created_at          TIMESTAMP
updated_at          TIMESTAMP
```

---

### 3️⃣ COURSE REGISTRATION (Student Enrollment)

```sql
TABLE: course_registrations
id                  INT PRIMARY KEY
student_id          INT FK → students.id
course_id           INT FK → courses.id
program_id          INT FK → programs.id
academic_year_id    INT FK → college_academic_years.id
semester_id         INT FK → college_semesters.id
registration_date   DATE
status              VARCHAR          -- registered, dropped, completed
attempt_number      INT DEFAULT 1    -- 1st attempt, 2nd attempt
is_retake           BOOLEAN DEFAULT 0
credit_hours        INT              -- From course
instructor_id       INT FK → hr_employees.id
company_id          INT FK
branch_id           INT FK
created_at          TIMESTAMP
updated_at          TIMESTAMP

UNIQUE (student_id, course_id, academic_year_id, semester_id, attempt_number)
```

---

### 4️⃣ ASSESSMENT TYPES & CONFIGURATION

```sql
TABLE: assessment_types
id                  INT PRIMARY KEY
name                VARCHAR          -- Assignment, Test, Quiz, Presentation, Practical
code                VARCHAR          -- ASS, TEST, QUIZ, PRES, PRAC
description         TEXT
default_weight      INT              -- Default % weight
max_score           INT              -- Default max score
is_active           BOOLEAN
company_id          INT FK
created_at          TIMESTAMP
updated_at          TIMESTAMP
```

```sql
TABLE: course_assessments
id                  INT PRIMARY KEY
course_id           INT FK → courses.id
assessment_type_id  INT FK → assessment_types.id
academic_year_id    INT FK
semester_id         INT FK
title               VARCHAR          -- e.g., "Assignment 1", "Midterm Test"
description         TEXT
weight_percentage   INT              -- % contribution to CA
max_marks           INT              -- Maximum score
assessment_date     DATE
due_date            DATE
instructor_id       INT FK → hr_employees.id
status              VARCHAR          -- draft, published, closed
company_id          INT FK
branch_id           INT FK
created_at          TIMESTAMP
updated_at          TIMESTAMP
```

---

### 5️⃣ CONTINUOUS ASSESSMENT (CA) SCORES

```sql
TABLE: assessment_scores
id                      INT PRIMARY KEY
course_registration_id  INT FK → course_registrations.id
course_assessment_id    INT FK → course_assessments.id
student_id              INT FK → students.id
course_id               INT FK → courses.id
score                   DECIMAL(5,2)     -- Actual score
max_marks               INT              -- Max for this assessment
weighted_score          DECIMAL(5,2)     -- Calculated weighted score
remarks                 TEXT
submitted_date          DATE
marked_by               INT FK → users.id
marked_date             DATE
status                  VARCHAR          -- pending, marked, published
created_at              TIMESTAMP
updated_at              TIMESTAMP

INDEX (student_id, course_id)
INDEX (course_registration_id)
```

---

### 6️⃣ FINAL EXAMS

```sql
TABLE: final_exams
id                  INT PRIMARY KEY
course_id           INT FK → courses.id
academic_year_id    INT FK
semester_id         INT FK
exam_code           VARCHAR
exam_name           VARCHAR
exam_date           DATE
exam_time           TIME
duration_minutes    INT
max_marks           INT              -- Usually 60 or 100
weight_percentage   INT              -- Usually 60%
venue               VARCHAR
instructions        TEXT
status              VARCHAR          -- scheduled, ongoing, completed
company_id          INT FK
branch_id           INT FK
created_at          TIMESTAMP
updated_at          TIMESTAMP
```

```sql
TABLE: final_exam_scores
id                      INT PRIMARY KEY
course_registration_id  INT FK → course_registrations.id
final_exam_id           INT FK → final_exams.id
student_id              INT FK → students.id
course_id               INT FK → courses.id
score                   DECIMAL(5,2)
max_marks               INT
weighted_score          DECIMAL(5,2)
remarks                 TEXT
marked_by               INT FK → users.id
marked_date             DATE
status                  VARCHAR          -- absent, marked, published
created_at              TIMESTAMP
updated_at              TIMESTAMP

INDEX (student_id, course_id)
INDEX (course_registration_id)
```

---

### 7️⃣ FINAL RESULTS (Consolidated)

```sql
TABLE: course_results
id                      INT PRIMARY KEY
course_registration_id  INT FK → course_registrations.id
student_id              INT FK → students.id
program_id              INT FK → programs.id
course_id               INT FK → courses.id
academic_year_id        INT FK
semester_id             INT FK
attempt_number          INT
credit_hours            INT

-- Marks Breakdown
ca_total                DECIMAL(5,2)     -- Out of 40
exam_total              DECIMAL(5,2)     -- Out of 60
total_marks             DECIMAL(5,2)     -- Out of 100

-- Grading
grade                   VARCHAR          -- A, B+, B, etc.
gpa_points              DECIMAL(3,2)     -- 5.0, 4.0, etc.
remark                  VARCHAR          -- Excellent, Pass, Fail

-- Additional Info
course_status           VARCHAR          -- Core, Elective
instructor_id           INT FK → hr_employees.id
is_retake               BOOLEAN
result_status           VARCHAR          -- draft, published, approved
remarks                 TEXT

-- Approval Workflow
published_by            INT FK → users.id
published_date          DATE
approved_by             INT FK → users.id
approved_date           DATE

company_id              INT FK
branch_id               INT FK
created_at              TIMESTAMP
updated_at              TIMESTAMP

UNIQUE (course_registration_id)
INDEX (student_id, academic_year_id, semester_id)
```

---

### 8️⃣ SEMESTER GPA

```sql
TABLE: semester_gpa
id                  INT PRIMARY KEY
student_id          INT FK → students.id
program_id          INT FK → programs.id
academic_year_id    INT FK
semester_id         INT FK
semester_name       VARCHAR          -- "Year 1 Semester 1"

-- GPA Calculation
total_courses       INT
total_credits       INT
total_quality_points DECIMAL(10,2)   -- Sum of (credit_hours × gpa_points)
semester_gpa        DECIMAL(3,2)     -- Quality points ÷ Total credits

status              VARCHAR          -- draft, published
remarks             TEXT
company_id          INT FK
branch_id           INT FK
created_at          TIMESTAMP
updated_at          TIMESTAMP

UNIQUE (student_id, academic_year_id, semester_id)
```

---

### 9️⃣ CUMULATIVE GPA (CGPA)

```sql
TABLE: cumulative_gpa
id                      INT PRIMARY KEY
student_id              INT FK → students.id
program_id              INT FK → programs.id

-- CGPA Calculation
total_semesters         INT
total_courses           INT
total_credits_attempted INT
total_credits_earned    INT
total_quality_points    DECIMAL(10,2)
cgpa                    DECIMAL(3,2)

-- Classification
class_of_award          VARCHAR          -- First Class, Second Upper, etc.
classification_date     DATE

status                  VARCHAR          -- active, graduated
remarks                 TEXT
company_id              INT FK
branch_id               INT FK
updated_at              TIMESTAMP
created_at              TIMESTAMP

UNIQUE (student_id)
```

---

### 🔟 TRANSCRIPT GENERATION (View/Table)

```sql
TABLE: transcripts
id                  INT PRIMARY KEY
student_id          INT FK
program_id          INT FK
academic_year_id    INT FK
semester_id         INT FK
transcript_type     VARCHAR          -- semester, cumulative, final
generated_by        INT FK → users.id
generated_date      DATE
issued_date         DATE
status              VARCHAR          -- draft, issued
pdf_path            VARCHAR
company_id          INT FK
branch_id           INT FK
created_at          TIMESTAMP
updated_at          TIMESTAMP
```

---

## 📊 CALCULATION FORMULAS

### CA Total (40%)
```
CA Total = SUM(weighted_score from assessment_scores) 
Normalized to 40% max
```

### Final Exam (60%)
```
Exam Weighted = (score / max_marks) × 60
```

### Total Marks
```
Total = CA Total + Exam Weighted
```

### GPA Calculation
```
Quality Points = Credit Hours × GPA Points (from grade)
Semester GPA = Total Quality Points ÷ Total Credit Hours
CGPA = Overall Quality Points ÷ Overall Credit Hours
```

---

## 🎯 KEY FEATURES

1. ✅ **Flexible Assessment Configuration** - Universities can define their own assessment types
2. ✅ **Course Registration Tracking** - Proper enrollment management
3. ✅ **Retake Support** - Track multiple attempts
4. ✅ **Approval Workflow** - Results go through approval before publishing
5. ✅ **Configurable Grading Scale** - Different scales for different programs
6. ✅ **Semester & Cumulative GPA** - Proper academic tracking
7. ✅ **Transcript Generation** - Automated transcript creation
8. ✅ **Multi-company Support** - Your existing company/branch structure

---

## 🔄 WORKFLOW

1. Student registers for courses → `course_registrations`
2. Assessments are configured → `course_assessments`
3. Scores are entered → `assessment_scores` (CA)
4. Final exam scores → `final_exam_scores`
5. Results calculated → `course_results`
6. GPA calculated → `semester_gpa`, `cumulative_gpa`
7. Transcript generated → `transcripts`

---

## ✅ NEXT STEPS

1. Review this structure
2. Confirm if this matches your requirements
3. I'll create all migrations
4. Build the complete system

Let me know if you want me to proceed with implementation!
