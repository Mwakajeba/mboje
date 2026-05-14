# Driver Trip Notification System

## Overview
This notification system automatically notifies drivers about their upcoming trips with full trip details including origin, destination, start time, and cargo information.

## Features

### 1. **Real-time Trip Notifications**
   - Automatic notifications when new trips are assigned
   - Shows trip number, origin, destination, start time, and cargo
   - Visual and sound alerts
   - Badge counter on notification icon

### 2. **Upcoming Trips View**
   - Tap notification bell to see all upcoming trips
   - Color-coded status badges
   - Time until trip starts
   - Detailed trip information

### 3. **Periodic Checking**
   - Automatically checks for new trips every 5 minutes
   - Only notifies once per trip
   - Smart notification history tracking

### 4. **Trip Details**
   - Full trip information
   - Customer details
   - Vehicle assignment
   - Cargo description
   - Route map

## Setup Instructions

### 1. Flutter Dependencies
The following packages have been added to `pubspec.yaml`:
```yaml
flutter_local_notifications: ^17.2.3
timezone: ^0.9.4
```

**Run the following command to install:**
```bash
cd driver_portal
flutter pub get
```

### 2. Platform-Specific Configuration

#### Android Configuration
Add the following to `android/app/src/main/AndroidManifest.xml` inside the `<application>` tag:

```xml
<!-- Notification permissions (Android 13+) -->
<uses-permission android:name="android.permission.POST_NOTIFICATIONS"/>

<!-- Notification channels -->
<receiver android:name="com.dexterous.flutterlocalnotifications.ScheduledNotificationReceiver" />
<receiver android:name="com.dexterous.flutterlocalnotifications.ScheduledNotificationBootReceiver">
    <intent-filter>
        <action android:name="android.intent.action.BOOT_COMPLETED"/>
    </intent-filter>
</receiver>
```

#### iOS Configuration
Add the following to `ios/Runner/Info.plist`:

```xml
<key>UIBackgroundModes</key>
<array>
    <string>fetch</string>
    <string>remote-notification</string>
</array>
```

### 3. Backend API Setup

#### Laravel Routes
The following API routes have been added to `routes/api.php`:

```php
// Driver trips endpoints
Route::prefix('driver')->middleware('auth:sanctum')->group(function () {
    Route::prefix('trips')->group(function () {
        Route::get('/', [DriverTripController::class, 'index']);
        Route::get('/upcoming', [DriverTripController::class, 'upcoming']);
        Route::get('/active', [DriverTripController::class, 'active']);
        Route::get('/{id}', [DriverTripController::class, 'show']);
        Route::post('/{id}/start', [DriverTripController::class, 'start']);
        Route::post('/{id}/update-location', [DriverTripController::class, 'updateLocation']);
        Route::post('/{id}/complete', [DriverTripController::class, 'complete']);
    });
});
```

#### Controller
A new `DriverTripController` has been created at:
`app/Http/Controllers/Api/DriverTripController.php`

This controller provides all the necessary endpoints for managing driver trips.

## How It Works

### 1. **Initialization**
When the app starts (`main.dart`):
```dart
await NotificationService().initialize();
await TripsService().initialize();
```

### 2. **Periodic Checking**
When the home screen loads:
```dart
TripsService().startPeriodicCheck(interval: const Duration(minutes: 5));
```

### 3. **Notification Flow**
1. App checks `/api/driver/trips/upcoming` every 5 minutes
2. New trips (not previously notified) trigger a notification
3. Trip ID is stored to prevent duplicate notifications
4. Notification shows with full trip details

### 4. **User Interaction**
- **Notification Bell Icon**: Shows badge with count of upcoming trips
- **Tap Bell**: Opens dialog with all upcoming trips
- **Tap Trip**: Navigate to trip details screen
- **Pull to Refresh**: Manually refresh upcoming trips

## API Endpoints

### 1. Get Upcoming Trips
```
GET /api/driver/trips/upcoming
Authorization: Bearer {token}

Response:
{
  "success": true,
  "data": [
    {
      "id": 123,
      "trip_number": "TR-2024-001",
      "status": "pending",
      "origin_location": "Dar es Salaam Warehouse",
      "destination_location": "Mwanza Distribution Center",
      "planned_start_date": "2024-02-09T08:00:00Z",
      "planned_end_date": "2024-02-10T18:00:00Z",
      "cargo_description": "Electronics - 50 boxes",
      "customer": {
        "id": 45,
        "name": "ABC Company Ltd"
      },
      "vehicle": {
        "id": 12,
        "registration_number": "T123 ABC",
        "name": "Truck 01"
      }
    }
  ]
}
```

### 2. Get All Trips
```
GET /api/driver/trips
Authorization: Bearer {token}
```

### 3. Get Active Trip
```
GET /api/driver/trips/active
Authorization: Bearer {token}
```

### 4. Get Trip Details
```
GET /api/driver/trips/{id}
Authorization: Bearer {token}
```

### 5. Start Trip
```
POST /api/driver/trips/{id}/start
Authorization: Bearer {token}

Body:
{
  "odometer_start": 12345,
  "location_latitude": -6.7924,
  "location_longitude": 39.2083,
  "notes": "Starting trip from warehouse"
}
```

### 6. Update Location
```
POST /api/driver/trips/{id}/update-location
Authorization: Bearer {token}

Body:
{
  "latitude": -6.7924,
  "longitude": 39.2083,
  "notes": "At checkpoint"
}
```

### 7. Complete Trip
```
POST /api/driver/trips/{id}/complete
Authorization: Bearer {token}

Body:
{
  "odometer_end": 12789,
  "notes": "Trip completed successfully"
}
```

## File Structure

```
driver_portal/
├── lib/
│   ├── services/
│   │   ├── notification_service.dart    # Handles local notifications
│   │   ├── trips_service.dart           # Manages trips and notifications
│   │   ├── api_service.dart             # API client
│   │   └── auth_service.dart            # Authentication
│   ├── screens/
│   │   └── home/
│   │       └── home_screen.dart         # Updated with notifications
│   └── main.dart                        # App initialization

backend/
├── app/
│   └── Http/
│       └── Controllers/
│           └── Api/
│               ├── DriverAuthController.php
│               └── DriverTripController.php   # New controller
└── routes/
    └── api.php                                 # Updated routes
```

## Customization

### Change Notification Check Interval
In `home_screen.dart`:
```dart
_tripsService.startPeriodicCheck(
  interval: const Duration(minutes: 10), // Change to 10 minutes
);
```

### Customize Notification Sound/Style
In `notification_service.dart`, modify the `AndroidNotificationDetails`:
```dart
const androidDetails = AndroidNotificationDetails(
  _channelId,
  _channelName,
  channelDescription: _channelDescription,
  importance: Importance.high,
  priority: Priority.high,
  showWhen: true,
  enableVibration: true,
  playSound: true,
  // Add custom sound:
  // sound: RawResourceAndroidNotificationSound('custom_sound'),
);
```

## Testing

### Test Notification System
1. Create a new trip in the admin panel and assign it to a driver
2. Wait for the periodic check (max 5 minutes) or close and reopen the app
3. Notification should appear with trip details
4. Tap notification bell to see the trip in upcoming trips list

### Manual Test
You can manually trigger a check by:
```dart
await TripsService().checkForNewTrips();
```

### Clear Notification History (for testing)
```dart
await TripsService().clearNotificationHistory();
```

## Troubleshooting

### Notifications Not Appearing
1. **Check permissions**: Ensure notification permissions are granted
2. **Check API**: Verify the `/api/driver/trips/upcoming` endpoint returns data
3. **Check logs**: Look for errors in Flutter console
4. **Android 13+**: Explicitly request POST_NOTIFICATIONS permission

### Duplicate Notifications
- The system tracks notified trip IDs in SharedPreferences
- If you're seeing duplicates, clear app data or call `clearNotificationHistory()`

### API Not Found Error
- Ensure backend routes are registered
- Run `php artisan route:list` to verify
- Check API base URL in `api_config.dart`

### Driver Has No Trips
- Verify the driver user has a FleetDriver record
- Check trips are assigned to the correct driver_id
- Ensure trip status is 'pending', 'scheduled', or 'planned'

## Future Enhancements

1. **Push Notifications**: Add FCM for instant notifications
2. **Trip Reminders**: Schedule notifications before trip starts
3. **Geofencing**: Notify when driver arrives at location
4. **Trip Updates**: Real-time updates on trip changes
5. **Chat**: In-app messaging with dispatch
6. **Offline Support**: Cache trips for offline access

## Support

For issues or questions:
1. Check the logs: `flutter logs`
2. Verify API responses: Use Postman to test endpoints
3. Check database: Ensure trips have correct driver_id
4. Review Laravel logs: `storage/logs/laravel.log`

## License

This notification system is part of the SmartAccounting Driver Portal application.

---

**Last Updated**: February 8, 2026
**Version**: 1.0.0
