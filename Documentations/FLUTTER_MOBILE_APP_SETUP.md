# Flutter Mobile App Setup Guide

## Project Structure

```
smartaccounting/
├── app/                          # Laravel Backend
├── routes/
│   └── api.php                   # API Routes
├── mobile_app/                   # Flutter Mobile Application
│   ├── lib/
│   │   ├── main.dart
│   │   ├── models/               # Data Models
│   │   ├── services/             # API Services
│   │   ├── screens/              # UI Screens
│   │   ├── widgets/              # Reusable Widgets
│   │   └── utils/                # Utilities
│   ├── pubspec.yaml
│   └── README.md
└── README.md
```

## Laravel Backend API

### Base URL Configuration

**Development:**
```
http://127.0.0.1:8000/api
```

**Production:**
```
https://yourdomain.com/api
```

### API Authentication

The API uses Laravel Sanctum for authentication. After login, you'll receive a token that must be included in all protected requests:

```
Authorization: Bearer {token}
```

## API Endpoints

### Authentication

#### POST `/api/parent/login`
Login as a parent/guardian.

**Request:**
```json
{
  "phone": "255123456789",
  "password": "password123"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "token": "1|xxxxxxxxxxxxxxxx",
    "token_type": "Bearer",
    "user": {
      "id": 1,
      "name": "Parent Name",
      "phone": "255123456789",
      "email": "parent@example.com"
    },
    "students": [
      {
        "id": 1,
        "name": "Student Name",
        "admission_number": "ADM001",
        "class": "Form 1",
        "stream": "A"
      }
    ]
  }
}
```

#### POST `/api/parent/logout`
Logout and revoke current token.

**Headers:**
```
Authorization: Bearer {token}
```

#### GET `/api/parent/me`
Get current authenticated parent information.

**Headers:**
```
Authorization: Bearer {token}
```

### Students

#### GET `/api/parent/students`
Get all students linked to the authenticated parent.

#### GET `/api/parent/students/{studentId}`
Get detailed information about a specific student.

#### GET `/api/parent/students/{studentId}/subjects`
Get all subjects for a student.

### Assignments

#### GET `/api/parent/students/{studentId}/assignments`
Get all assignments for a student.

**Query Parameters:**
- `status`: pending, submitted, graded (optional)
- `subject_id`: Filter by subject (optional)
- `date_from`: Start date (optional)
- `date_to`: End date (optional)

#### GET `/api/parent/students/{studentId}/assignments/{assignmentId}`
Get detailed information about a specific assignment.

#### POST `/api/parent/students/{studentId}/assignments/{assignmentId}/submit`
Submit an assignment.

**Request:**
```json
{
  "submission_type": "written|online_upload|photo_upload",
  "submission_content": "Text content or file path",
  "notes": "Optional notes"
}
```

### Attendance

#### GET `/api/parent/students/{studentId}/attendance`
Get attendance records for a student.

**Query Parameters:**
- `start_date`: Start date (optional)
- `end_date`: End date (optional)
- `status`: present, absent, late, sick (optional)

#### GET `/api/parent/students/{studentId}/attendance/stats`
Get attendance statistics for a student.

#### GET `/api/parent/students/{studentId}/attendance/calendar`
Get attendance calendar view for a student.

### Exams and Results

#### GET `/api/parent/students/{studentId}/exams`
Get all exams for a student.

#### GET `/api/parent/students/{studentId}/exams/{examTypeId}/{academicYearId}`
Get detailed exam results.

#### GET `/api/parent/students/{studentId}/results`
Get all results for a student.

#### GET `/api/parent/students/{studentId}/results/{examTypeId}`
Get results filtered by exam type.

### Fees and Payments

#### GET `/api/parent/students/{studentId}/fees`
Get fee information for a student.

#### GET `/api/parent/students/{studentId}/fees/invoices`
Get all invoices for a student.

#### GET `/api/parent/students/{studentId}/fees/payments`
Get payment history for a student.

#### GET `/api/parent/students/{studentId}/fees/balance`
Get current fee balance for a student.

#### POST `/api/parent/students/{studentId}/fees/payment`
Make a payment.

**Request:**
```json
{
  "invoice_id": 1,
  "amount": 50000,
  "payment_method": "mpesa|bank|cash",
  "reference": "Payment reference",
  "notes": "Optional notes"
}
```

### Notifications

#### GET `/api/parent/notifications`
Get all notifications for the parent.

**Query Parameters:**
- `page`: Page number (optional)
- `per_page`: Items per page (optional)
- `type`: Filter by type (optional)

#### GET `/api/parent/notifications/unread`
Get unread notifications count.

#### PUT `/api/parent/notifications/{notificationId}/read`
Mark a notification as read.

#### PUT `/api/parent/notifications/read-all`
Mark all notifications as read.

### Academic Information

#### GET `/api/parent/students/{studentId}/academic-info`
Get academic information (class, stream, academic year, etc.).

#### GET `/api/parent/students/{studentId}/timetable`
Get student timetable.

#### GET `/api/parent/students/{studentId}/events`
Get school events and calendar.

## Flutter Project Setup

### 1. Create Flutter Project

```bash
cd "C:\Users\REVOCATUS BALTHAZAR\Music\smartaccounting"
flutter create mobile_app --org com.schoolportal --project-name mobile_app
```

### 2. Required Dependencies

Add to `mobile_app/pubspec.yaml`:

```yaml
dependencies:
  flutter:
    sdk: flutter
  
  # HTTP & API
  http: ^1.1.0
  dio: ^5.4.0
  
  # State Management
  provider: ^6.1.1
  # or
  # bloc: ^8.1.3
  # flutter_bloc: ^8.1.4
  
  # Local Storage
  shared_preferences: ^2.2.2
  flutter_secure_storage: ^9.0.0
  
  # JSON Serialization
  json_annotation: ^4.8.1
  
  # UI
  flutter_svg: ^2.0.9
  cached_network_image: ^3.3.0
  flutter_spinkit: ^5.2.0
  
  # Utils
  intl: ^0.19.0
  url_launcher: ^6.2.2
  image_picker: ^1.0.5
  file_picker: ^6.1.1

dev_dependencies:
  flutter_test:
    sdk: flutter
  flutter_lints: ^3.0.1
  build_runner: ^2.4.7
  json_serializable: ^6.7.1
```

### 3. Project Structure

```
mobile_app/
├── lib/
│   ├── main.dart
│   ├── app.dart
│   ├── config/
│   │   └── api_config.dart          # API base URL and configuration
│   ├── models/
│   │   ├── user.dart
│   │   ├── student.dart
│   │   ├── assignment.dart
│   │   ├── attendance.dart
│   │   ├── exam.dart
│   │   ├── fee.dart
│   │   └── notification.dart
│   ├── services/
│   │   ├── api_service.dart         # Base API service
│   │   ├── auth_service.dart        # Authentication service
│   │   ├── student_service.dart
│   │   ├── assignment_service.dart
│   │   ├── attendance_service.dart
│   │   ├── exam_service.dart
│   │   ├── fee_service.dart
│   │   └── notification_service.dart
│   ├── screens/
│   │   ├── auth/
│   │   │   ├── login_screen.dart
│   │   │   └── splash_screen.dart
│   │   ├── home/
│   │   │   └── home_screen.dart
│   │   ├── students/
│   │   │   └── student_list_screen.dart
│   │   ├── assignments/
│   │   │   ├── assignment_list_screen.dart
│   │   │   └── assignment_detail_screen.dart
│   │   ├── attendance/
│   │   │   └── attendance_screen.dart
│   │   ├── exams/
│   │   │   └── exam_results_screen.dart
│   │   ├── fees/
│   │   │   └── fees_screen.dart
│   │   └── notifications/
│   │       └── notifications_screen.dart
│   ├── widgets/
│   │   ├── custom_button.dart
│   │   ├── custom_text_field.dart
│   │   └── loading_indicator.dart
│   └── utils/
│       ├── constants.dart
│       ├── helpers.dart
│       └── validators.dart
├── pubspec.yaml
└── README.md
```

## Running the Applications

### Laravel Backend

```bash
# Start Laravel development server
php artisan serve

# The API will be available at:
# http://127.0.0.1:8000/api
```

### Flutter Mobile App

```bash
# Navigate to Flutter project
cd mobile_app

# Get dependencies
flutter pub get

# Run on connected device/emulator
flutter run

# Or run on specific device
flutter run -d <device-id>
```

## Development Workflow

1. **Backend Development:**
   - Add/modify API routes in `routes/api.php`
   - Create/update controllers in `app/Http/Controllers/Api/`
   - Test APIs using Postman or similar tools

2. **Frontend Development:**
   - Update API service classes in `mobile_app/lib/services/`
   - Create/update screens in `mobile_app/lib/screens/`
   - Test on device/emulator

3. **Communication:**
   - Flutter app makes HTTP requests to Laravel API
   - Laravel API returns JSON responses
   - Flutter app parses and displays data

## Important Notes

1. **CORS Configuration:** Ensure Laravel CORS is configured to allow requests from Flutter app
2. **Token Storage:** Store authentication tokens securely using `flutter_secure_storage`
3. **Error Handling:** Implement proper error handling for network requests
4. **Offline Support:** Consider implementing local caching for offline functionality
5. **API Versioning:** Consider versioning your API (e.g., `/api/v1/`) for future updates

## Next Steps

1. Create Flutter project structure
2. Implement API service classes
3. Create authentication flow
4. Build main screens
5. Implement data models
6. Add error handling and loading states
7. Test on real devices

