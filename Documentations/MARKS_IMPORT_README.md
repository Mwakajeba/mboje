# Marks Import Functionality

## Overview
The marks import feature allows teachers and administrators to bulk import examination marks from Excel files, making it easier to enter marks for large numbers of students.

## How It Works

### Step 1: Download Sample Template
1. Go to the Marks Entry page
2. Select an Exam Type, Class, and optionally a Stream
3. Click "Import Marks" button
4. Click "Download Sample Excel" to get a template with:
   - Student information (Admission Number, Name, Class, Stream)
   - Subject columns for entering marks
   - Pre-filled student data based on your selections

### Step 2: Fill the Template
1. Open the downloaded Excel file
2. Enter marks (0-100) in the subject columns for each student
3. Leave cells blank for absent students
4. Save the file

### Step 3: Upload and Import
1. Click "Choose Excel File" and select your completed file
2. Click "Import Marks" to upload and process the file
3. The system will:
   - Validate the file format
   - Match students by admission number or name
   - Find the correct exam assignments for each subject
   - Save/update marks in the database
   - Show a summary of successful imports and any errors

## Technical Implementation

### Files Created/Modified:
- `app/Imports/MarksImport.php` - Handles Excel file processing and data validation
- `app/Http/Controllers/School/AcademicsExaminationsController.php` - Added `importMarks()` method
- `routes/web.php` - Added import route
- `resources/views/school/academics-examinations/marks-entry.blade.php` - Updated frontend with import functionality

### Data Validation:
- Excel file format validation (.xlsx, .xls)
- Student matching by admission number or name
- Subject matching by name/short_name
- Mark range validation (0-100)
- Exam assignment validation (ensures subject is assigned to the class)

### Error Handling:
- Invalid file formats
- Missing students or subjects
- Invalid mark values
- Missing exam assignments
- Database transaction rollback on errors

### Security:
- Company and branch filtering
- User authentication required
- File size limits (10MB max)
- CSRF protection

## Usage Flow:
1. User downloads sample Excel template
2. User fills in marks and saves file
3. User uploads file through web interface
4. System validates and processes the data
5. System saves marks to database
6. User sees success/error summary
7. Marks are immediately visible in the marks entry table

## Benefits:
- Faster bulk entry for large classes
- Reduced manual data entry errors
- Consistent data format
- Immediate validation feedback
- Integration with existing marks entry system