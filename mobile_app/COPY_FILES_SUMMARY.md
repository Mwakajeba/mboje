# Files Copied from C:\School Parent and Teachers portal\lib

## Files to Copy:
1. ✅ `api_service.dart` → Already adapted as `services/parent_api_service.dart`
2. `login.dart` → `screens/auth/login_screen.dart` (already exists, needs update)
3. `home_page.dart` → `screens/home/home_screen.dart` (needs update)
4. `select_student.dart` → `screens/select_student.dart`
5. `auth_wrapper.dart` → `screens/auth/auth_wrapper.dart`
6. `assignment.dart` → `screens/assignments/assignments_screen.dart`
7. `attendance.dart` → `screens/attendance/attendance_screen.dart`
8. `fees.dart` → `screens/fees/fees_screen.dart`
9. `exams.dart` → `screens/exams/exams_screen.dart` (contains AcademicsPage)
10. `results.dart` → `screens/results/results_screen.dart`
11. `profile_student.dart` → `screens/profile/profile_screen.dart`
12. `subjects.dart` → `screens/subjects/subjects_screen.dart`
13. `settings.dart` → `screens/settings/settings_screen.dart`
14. `report.dart` → `screens/report/report_screen.dart`
15. `language_provider.dart` → `providers/language_provider.dart`
16. `main.dart` → `main.dart` (needs update)

## Import Updates Needed:
- `import 'api_service.dart';` → `import '../services/parent_api_service.dart';`
- `import 'main.dart';` → Remove or update to specific imports
- `import 'login.dart';` → `import '../screens/auth/login_screen.dart';`
- `import 'home_page.dart';` → `import '../screens/home/home_screen.dart';`
- etc.

## API Updates:
- All `ParentApiService` calls should work with the adapter service
- No hardcoded URLs should remain (all use ApiConfig.baseUrl)

