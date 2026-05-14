# Overtime Rules & Requests - Payroll Integration Guide

## Current Implementation Overview

### How Overtime is Currently Linked to Payroll

Overtime is **NOT** linked through Salary Components. Instead, it follows a **separate calculation path** that integrates with payroll processing.

---

## 🔗 LINKING MECHANISM

### **1. Overtime Rules → Overtime Rate Determination**

**Purpose:** Define overtime multipliers (1.5x, 2.0x, etc.) based on job grade and day type.

**Location:** `app/Models/Hr/OvertimeRule`

**Fields:**
- `grade_id` (nullable) - Links to Job Grade (optional)
- `day_type` - Weekday, Weekend, or Holiday
- `overtime_rate` - Multiplier (e.g., 1.50, 2.00)
- `max_hours_per_day` - Maximum overtime hours allowed
- `requires_approval` - Whether approval is needed

**How it works:**
1. When calculating overtime, the system looks up the appropriate rule based on:
   - Employee's Job Grade (from Position → Job Grade)
   - Day Type (weekday/weekend/holiday)
   - Company ID

2. The rule provides the `overtime_rate` multiplier (e.g., 1.5x for weekdays, 2.0x for weekends)

**Example:**
```
Employee: John Doe
Position: Senior Developer → Job Grade: G5
Date: Saturday (Weekend)
Rule Found: Grade G5, Weekend → Rate: 2.0x
```

---

### **2. Overtime Requests → Approved Overtime Hours**

**Purpose:** Track and approve individual overtime requests.

**Location:** `app/Models/Hr/OvertimeRequest`

**Fields:**
- `employee_id` - Links to employee
- `attendance_id` - Optional link to attendance record
- `overtime_date` - Date of overtime
- `overtime_hours` - Number of hours worked
- `overtime_rate` - Rate multiplier (stored at request time)
- `status` - pending, approved, rejected

**Workflow:**
1. Employee/HR creates overtime request
2. Request goes through approval workflow (if required)
3. When approved, overtime hours are added to attendance record (if linked)
4. Approved requests are used in payroll calculation

---

### **3. Payroll Processing → Overtime Earnings Calculation**

**Current Implementation:**

#### **In `PayrollController::process()` (Lines 667-671):**
```php
// Get approved overtime hours for the payroll period
$overtimeHours = $this->attendanceService->getApprovedOvertimeHours($employee, $startDate, $endDate);

// Calculate hourly rate from basic salary
$hourlyRate = $basicSalary / 22 / 8; // Assuming 22 working days, 8 hours per day

// Get overtime rate from rules (based on day type and grade)
$overtimeRate = $this->attendanceService->getOvertimeRate($employee, $startDate, $companyId);

// Calculate overtime earnings
$overtimeEarnings = $overtimeHours * $hourlyRate * $overtimeRate;
```

#### **In `PayrollCalculationService::calculateEarnings()` (Lines 142-145):**
```php
// Calculate overtime (from attendance)
$overtimeData = $this->calculateOvertime($employee, $date, $companyId);
$overtime = $overtimeData['amount'];
$taxableGross += $overtime;
```

**⚠️ ISSUE:** The `calculateOvertime()` method currently returns `['amount' => 0, 'hours' => 0]` (not implemented).

---

## 📊 DATA FLOW

```
┌─────────────────────┐
│  Overtime Rules     │
│  (Configuration)     │
│  - Grade-based       │
│  - Day-type rates    │
└──────────┬──────────┘
           │
           │ Provides overtime_rate
           │
           ▼
┌─────────────────────┐
│  Overtime Requests  │
│  (Individual)       │
│  - Hours worked     │
│  - Approval status  │
└──────────┬──────────┘
           │
           │ Approved requests
           │
           ▼
┌─────────────────────┐
│  Attendance Records │
│  - overtime_hours   │
│  (Updated on approval)│
└──────────┬──────────┘
           │
           │ Aggregated hours
           │
           ▼
┌─────────────────────┐
│  Payroll Processing │
│  - Gets hours       │
│  - Gets rate        │
│  - Calculates:      │
│    Hours × Hourly   │
│    Rate × Overtime  │
│    Rate             │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│  Gross Earnings     │
│  (Added to salary)  │
└─────────────────────┘
```

---

## 🔍 KEY INTEGRATION POINTS

### **1. Overtime Rate Lookup**
**Service:** `AttendanceService::getOvertimeRate()`

**Logic:**
1. Determines day type (weekday/weekend/holiday)
2. Gets employee's job grade from Position
3. Finds matching `OvertimeRule`:
   - First: Grade-specific rule for that day type
   - Fallback: Company-wide rule for that day type
   - Default: 1.5x if no rule found

### **2. Approved Overtime Hours**
**Service:** `AttendanceService::getApprovedOvertimeHours()`

**Logic:**
1. Queries `OvertimeRequest` table
2. Filters by:
   - Employee ID
   - Date range (payroll period)
   - Status = 'approved'
3. Sums `overtime_hours`

### **3. Payroll Calculation**
**Location:** `PayrollController::process()`

**Formula:**
```
Overtime Earnings = Approved Overtime Hours × Hourly Rate × Overtime Rate

Where:
- Hourly Rate = Basic Salary ÷ 22 days ÷ 8 hours
- Overtime Rate = From OvertimeRule (based on grade + day type)
```

---

## ❓ IS OVERTIME A SALARY COMPONENT?

**Current Answer: NO**

Overtime is **NOT** managed as a Salary Component because:

1. **Dynamic Nature:** Overtime varies month-to-month based on actual work
2. **Approval Required:** Overtime must be approved before inclusion in payroll
3. **Rate Variability:** Overtime rates change based on day type (weekday/weekend/holiday)
4. **Attendance-Based:** Overtime is calculated from attendance records, not fixed amounts

**However, Overtime Earnings are:**
- Added to gross salary
- Included in taxable income
- Subject to statutory deductions (PAYE, Pension, etc.)

---

## 🔧 CURRENT GAPS & RECOMMENDATIONS

### **✅ Gap 1: `PayrollCalculationService::calculateOvertime()` - IMPLEMENTED**

**Status:** ✅ **COMPLETED**

The method has been fully implemented to:
1. ✅ Get approved overtime requests for the payroll period
2. ✅ Calculate hourly rate from basic salary (Base Salary ÷ 22 days ÷ 8 hours)
3. ✅ Use stored overtime rate from request, or get from rules (fallback)
4. ✅ Calculate overtime earnings per request: `Hours × Hourly Rate × Overtime Rate`
5. ✅ Return calculated amount, hours, and detailed breakdown

**Implementation Details:**
- Queries `OvertimeRequest` table for approved requests in the payroll period
- Uses `getBaseSalary()` to get the base salary for hourly rate calculation
- Respects the `overtime_rate` stored in each request (captured at approval time)
- Falls back to `AttendanceService::getOvertimeRate()` if no rate is stored
- Returns detailed breakdown with date, hours, rates, and amount per request

### **Gap 2: Overtime Not in Salary Structure**

**Current State:** Overtime is calculated separately, not as a salary component.

**Options:**

#### **Option A: Keep Separate (Current - Recommended)**
- ✅ Pros: Flexible, approval-based, dynamic
- ✅ Pros: Matches real-world workflow
- ❌ Cons: Not visible in salary structure preview

#### **Option B: Add as Salary Component**
- ✅ Pros: Visible in salary structure
- ❌ Cons: Would need to be recalculated each month
- ❌ Cons: Doesn't fit component model (components are fixed/percentage/formula)

**Recommendation:** Keep separate but improve integration.

---

## 📝 IMPROVEMENT RECOMMENDATIONS

### **✅ 1. Implement `calculateOvertime()` in PayrollCalculationService - COMPLETED**

**Status:** ✅ **IMPLEMENTED**

The method has been implemented with the following features:
- ✅ Gets approved overtime requests for the payroll period
- ✅ Calculates hourly rate from base salary
- ✅ Uses stored overtime rate from each request (captured at approval)
- ✅ Falls back to rule-based rate if not stored
- ✅ Calculates earnings per request with detailed breakdown
- ✅ Returns amount, hours, and breakdown array

**See:** `app/Services/Hr/PayrollCalculationService.php` (lines 490-569)

### **2. Add Overtime Breakdown to Payroll Response**

Include overtime breakdown in payroll calculation response:

```php
'overtime' => $earnings['overtime'],
'overtime_hours' => $earnings['overtime_hours'],
'overtime_breakdown' => [
    'weekday_hours' => $weekdayHours,
    'weekend_hours' => $weekendHours,
    'holiday_hours' => $holidayHours,
    'weekday_amount' => $weekdayAmount,
    'weekend_amount' => $weekendAmount,
    'holiday_amount' => $holidayAmount,
]
```

### **3. Link Overtime Requests to Payroll Records**

Add a relationship to track which overtime requests were included in which payroll:

```php
// In PayrollEmployee model
public function overtimeRequests()
{
    return $this->hasMany(OvertimeRequest::class, 'employee_id', 'employee_id')
        ->whereBetween('overtime_date', [
            Carbon::create($this->payroll->year, $this->payroll->month, 1)->startOfMonth(),
            Carbon::create($this->payroll->year, $this->payroll->month, 1)->endOfMonth()
        ])
        ->where('status', 'approved');
}
```

---

## ✅ SUMMARY

**How Overtime Links to Payroll:**

1. **Overtime Rules** → Define rates (1.5x, 2.0x) by grade and day type
2. **Overtime Requests** → Track individual overtime with approval workflow
3. **Attendance Records** → Store approved overtime hours
4. **Payroll Processing** → Calculates: `Hours × Hourly Rate × Overtime Rate`
5. **Gross Earnings** → Overtime added to gross salary (taxable)

**Key Points:**
- Overtime is **NOT** a Salary Component (it's dynamic and approval-based)
- Overtime Rules determine the **rate multiplier**
- Overtime Requests provide the **approved hours**
- Payroll calculates overtime **separately** from salary components
- Overtime earnings are **added to gross** and subject to statutory deductions

**✅ Implementation Status:**
- ✅ `PayrollCalculationService::calculateOvertime()` fully implemented
- ✅ Overtime calculation integrated into payroll service
- ✅ Returns detailed breakdown with hours, rates, and amounts per request
- ✅ Overtime hours and breakdown included in payroll calculation response

