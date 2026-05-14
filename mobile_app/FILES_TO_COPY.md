# Files to Copy from C:\School Parent and Teachers portal\lib

## Status: ✅ Core Files Copied
- ✅ `services/parent_api_service.dart` - Adapter service created
- ✅ `providers/language_provider.dart` - Language provider copied

## Files Still Needed:

### 1. Core Navigation & Auth
- [ ] `screens/auth/auth_wrapper.dart` - Update imports from old structure
- [ ] `screens/select_student.dart` - Update imports (import '../screens/home/home_screen.dart' instead of 'main.dart')

### 2. Main Screens
- [ ] `screens/home/home_screen.dart` - Update all imports
- [ ] `screens/assignments/assignments_screen.dart` - Copy from `assignment.dart`
- [ ] `screens/attendance/attendance_screen.dart` - Copy from `attendance.dart`
- [ ] `screens/fees/fees_screen.dart` - Copy from `fees.dart`
- [ ] `screens/exams/exams_screen.dart` - Copy from `exams.dart` (contains AcademicsPage)
- [ ] `screens/results/results_screen.dart` - Copy from `results.dart`
- [ ] `screens/profile/profile_screen.dart` - Copy from `profile_student.dart`
- [ ] `screens/subjects/subjects_screen.dart` - Copy from `subjects.dart`
- [ ] `screens/settings/settings_screen.dart` - Copy from `settings.dart`
- [ ] `screens/report/report_screen.dart` - Copy from `report.dart`

### 3. Main Entry
- [ ] `main.dart` - Update to use new structure and all new screens

## Import Updates Required:

For each file, update imports:
- `import 'api_service.dart';` → `import '../services/parent_api_service.dart';`
- `import 'main.dart';` → Remove or update to specific file imports
- `import 'login.dart';` → `import '../screens/auth/login_screen.dart';`
- `import 'home_page.dart';` → `import '../screens/home/home_screen.dart';`
- `import 'select_student.dart';` → `import '../screens/select_student.dart';`
- `import 'language_provider.dart';` → `import '../providers/language_provider.dart';`
- `import 'assignment.dart';` → `import '../screens/assignments/assignments_screen.dart';`
- etc.

## API Updates:
- All files already use `ParentApiService` which is now an adapter
- No hardcoded URLs should remain (all use ApiConfig.baseUrl via the adapter)

## Next Steps:
1. Copy remaining files one by one
2. Update imports in each file
3. Test each screen
4. Update main.dart to wire everything together

