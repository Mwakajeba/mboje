# 📊 GPA/CGPA Calculation System Documentation

## 🎓 Overview

This document explains the comprehensive GPA (Grade Point Average) and CGPA (Cumulative Grade Point Average) calculation system implemented in the SmartAccounting College Module.

---

## 🧮 What is GPA?

**GPA (Grade Point Average)** is the weighted average of your academic performance across all courses in a semester, taking into account the credit value of each course.

**CGPA (Cumulative Grade Point Average)** is the overall average GPA across ALL semesters/years.

---

## 📋 Grading Scale (5-Point System)

| Marks (%)  | Grade | Grade Point | Remark         | Status |
|------------|-------|-------------|----------------|--------|
| 70 – 100   | A     | **5**       | Excellent      | Pass   |
| 60 – 69    | B     | **4**       | Very Good      | Pass   |
| 50 – 59    | C     | **3**       | Good           | Pass   |
| 40 – 49    | D     | **2**       | Pass           | Pass   |
| 35 – 39    | E     | **1**       | Marginal Pass  | Pass   |
| 0 – 34     | F     | **0**       | Fail           | Fail   |

---

## 📐 GPA Calculation Formula

### Step 1: Calculate Quality Points for Each Course

```
Quality Points = Credit Hours × Grade Point
```

### Step 2: Calculate Semester GPA

```
Semester GPA = Σ(Credit Hours × Grade Points) ÷ Σ(Credit Hours)
             = Total Quality Points ÷ Total Credit Hours
```

### Step 3: Calculate CGPA (All Semesters)

```
CGPA = Overall Quality Points ÷ Overall Credit Hours
```

---

## 📝 Example Calculation

### Student's Semester Results:

| Course           | Credit | Marks | Grade | GP | Quality Points |
|------------------|--------|-------|-------|----|----------------|
| Mathematics      | 3      | 75%   | A     | 5  | 3 × 5 = 15     |
| Computer Science | 4      | 62%   | B     | 4  | 4 × 4 = 16     |
| Physics          | 3      | 55%   | C     | 3  | 3 × 3 = 9      |
| Chemistry        | 3      | 42%   | D     | 2  | 3 × 2 = 6      |
| English          | 2      | 38%   | E     | 1  | 2 × 1 = 2      |

### Calculation:

```
Total Quality Points = 15 + 16 + 9 + 6 + 2 = 48
Total Credit Hours   = 3 + 4 + 3 + 3 + 2 = 15

Semester GPA = 48 ÷ 15 = 3.20
```

---

## 🏆 GPA Classification (Degree Class)

| GPA Range   | Classification          | Code  |
|-------------|-------------------------|-------|
| 4.50 – 5.00 | First Class Honours     | 1st   |
| 3.50 – 4.49 | Second Class Upper      | 2:1   |
| 2.40 – 3.49 | Second Class Lower      | 2:2   |
| 1.50 – 2.39 | Third Class             | 3rd   |
| 1.00 – 1.49 | Pass                    | Pass  |
| Below 1.00  | Fail                    | Fail  |

---

## 🔄 CGPA Example (Multiple Semesters)

### Year 1 Semester 1:
- Total Quality Points: 48
- Total Credits: 15
- GPA: 3.20

### Year 1 Semester 2:
- Total Quality Points: 60
- Total Credits: 18
- GPA: 3.33

### CGPA Calculation:

```
Overall Quality Points = 48 + 60 = 108
Overall Credit Hours   = 15 + 18 = 33

CGPA = 108 ÷ 33 = 3.27

Classification: Second Class Lower (2:2)
```

---

## 🛠️ Technical Implementation

### Service Class: `App\Services\College\GPACalculatorService`

#### Available Methods:

```php
// Get grade for marks
$grade = $service->getGradeForMarks(75.5);
// Returns: ['grade' => 'A', 'gpa' => 5.0, 'remark' => 'Excellent', 'pass' => true]

// Calculate Semester GPA
$gpa = $service->calculateSemesterGPA($studentId, $academicYearId, $semesterId);

// Calculate CGPA
$cgpa = $service->calculateCGPA($studentId);

// Get comprehensive summary
$summary = $service->getStudentAcademicSummary($studentId);

// Check graduation eligibility
$status = $service->canGraduate($studentId, 1.0);

// Get students by classification
$students = $service->getStudentsByClassification('1st', $programId);
```

---

## 📊 Assessment Weight Configuration

| Component              | Weight |
|------------------------|--------|
| Continuous Assessment  | 40%    |
| Final Examination      | 60%    |
| **Total**              | 100%   |

### Total Marks Calculation:

```
Total Marks = (CA Score × 0.40) + (Exam Score × 0.60)
```

---

## ⚠️ Important Rules

1. **Credit Hours**: Each course has assigned credit hours (typically 2-4)
2. **Minimum Pass Mark**: 35% (Grade E)
3. **Retake Policy**: Failed courses must be retaken
4. **GPA Precision**: Calculated to 2 decimal places
5. **Quality Points**: Always = Credit Hours × Grade Point

---

## 📁 Related Files

- `app/Services/College/GPACalculatorService.php` - GPA calculation service
- `app/Http/Controllers/College/CourseResultController.php` - Course results controller
- `app/Models/College/CourseResult.php` - Course result model
- `app/Models/College/GradingScale.php` - Grading scale model
- `resources/views/college/student-portal/transcript.blade.php` - Transcript view

---

## 🔧 Configuration

The grading scale and weights can be configured in:

1. **Controller**: `CourseResultController::$gradingScale`
2. **Service**: `GPACalculatorService::$gradingScale`
3. **Database**: `grading_scales` and `grading_scale_items` tables

---

## 📈 Features

- ✅ Semester GPA calculation
- ✅ Cumulative GPA (CGPA) calculation
- ✅ Yearly GPA calculation
- ✅ Automatic grade assignment
- ✅ Quality points calculation
- ✅ Classification determination
- ✅ Pass/Fail status tracking
- ✅ Graduation eligibility check
- ✅ Batch GPA reports with rankings
- ✅ Student transcript generation

---

*Last Updated: December 2025*
