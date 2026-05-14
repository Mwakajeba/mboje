# School Portal Mobile App

Flutter mobile application for School Parent and Teachers Portal.

## Project Location

This Flutter project is located inside the Laravel project directory for organizational purposes only. The two applications run independently.

## Setup Instructions

### 1. Install Dependencies

```bash
flutter pub get
```

### 2. Configure API Base URL

Edit `lib/config/api_config.dart` and set your API base URL:

```dart
const String apiBaseUrl = 'http://127.0.0.1:8000/api'; // Development
// const String apiBaseUrl = 'https://yourdomain.com/api'; // Production
```

### 3. Run the App

```bash
# Run on connected device/emulator
flutter run

# Run on specific device
flutter devices  # List available devices
flutter run -d <device-id>
```

## Project Structure

```
lib/
├── main.dart                 # App entry point
├── app.dart                  # Main app widget
├── config/                   # Configuration files
│   └── api_config.dart      # API configuration
├── models/                   # Data models
├── services/                 # API services
├── screens/                  # UI screens
├── widgets/                  # Reusable widgets
└── utils/                    # Utilities
```

## Development

- **Backend API:** Laravel backend at parent directory
- **API Documentation:** See `FLUTTER_MOBILE_APP_SETUP.md` in parent directory
- **State Management:** Provider (or Bloc if preferred)

## Building for Production

### Android

```bash
flutter build apk --release
```

### iOS

```bash
flutter build ios --release
```

## Notes

- This app communicates with Laravel backend via RESTful APIs only
- Authentication uses Laravel Sanctum tokens
- Store tokens securely using `flutter_secure_storage`
- Handle network errors gracefully
- Implement offline caching if needed

