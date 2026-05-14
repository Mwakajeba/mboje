# Driver Portal - Countdown Timer & Live GPS Map Features

## Overview
This document describes the new countdown timer and live GPS map features added to the driver portal.

## Features Implemented

### 1. Countdown Timer for Upcoming Trips (Safari Zinazokuja)

#### What It Does:
- Shows real-time countdown for each upcoming trip
- Displays time remaining in:
  - **Days & Hours** (for trips more than 1 day away) - Blue color
  - **Hours & Minutes** (for trips within 24 hours) - Orange color  
  - **Minutes** (for trips within 1 hour) - Red color
  - **"Starting now!"** when trip time arrives - Red color

#### Alert System:
- **24-Hour Alert**: Shows notification when trip is within 24 hours
- **Trip Starting Alert**: Shows dialog when trip is within 5 minutes
- **Alarm Sound**: Plays alarm sound automatically when trip is starting (within 5 minutes)
- **Auto-Refresh**: Countdown updates every minute automatically

#### Languages Supported:
- **Swahili**: "Siku X, Masaa Y zilizobaki" | "Masaa X, Dakika Y zilizobaki"
- **English**: "X days, Y hrs left" | "X hrs, Y mins left"

#### Technical Details:
```dart
// Timer updates every minute
Timer.periodic(const Duration(minutes: 1), ...);

// Checks for approaching trips and plays alarm
_checkAndPlayAlarmForApproachingTrips();

// Countdown calculation
final diff = startDate.difference(now);
if (diff.inDays >= 1) {
  // Show days & hours
} else if (diff.inHours >= 1) {
  // Show hours & minutes
} else {
  // Show minutes only
}
```

### 2. Live GPS Map on Confirm Trip Start Screen (Thibitisha Safari)

#### What It Does:
- Shows **real-time Google Map** with driver's current location
- Displays live GPS indicator (green dot = active location)
- Shows blue marker at driver's current position
- Includes "My Location" button to recenter map
- Requests location permissions automatically

#### Features:
- **Live Location**: Real-time GPS tracking
- **Map Controls**: 
  - Zoom in/out with pinch gestures
  - Pan to explore area
  - "My Location" button to recenter
  - Compass for orientation
- **Status Indicator**: Green dot shows GPS is active
- **Permission Handling**: Automatically requests location permissions

#### Technical Details:
```dart
// Uses Google Maps Flutter
GoogleMap(
  initialCameraPosition: CameraPosition(...),
  markers: _markers,
  myLocationEnabled: true,
  onMapCreated: (controller) => _mapController = controller,
);

// Gets current location
final position = await Geolocator.getCurrentPosition(
  desiredAccuracy: LocationAccuracy.high,
);

// Updates marker
Marker(
  markerId: MarkerId('current_location'),
  position: LatLng(position.latitude, position.longitude),
);
```

## Setup Instructions

### 1. Install Dependencies

Run the following command in the driver_portal directory:
```bash
flutter pub get
```

This will install the new dependencies:
- `google_maps_flutter: ^2.5.0` - For displaying maps
- `geolocator: ^10.1.0` - For GPS location
- `permission_handler: ^11.0.1` - For location permissions
- `audioplayers: ^5.2.1` - For alarm sounds

### 2. Add Google Maps API Key

**Important**: You need a Google Maps API key for the map to work.

1. Get a Google Maps API key from: https://console.cloud.google.com/
2. Enable "Maps SDK for Android" and "Maps SDK for iOS"
3. Replace `YOUR_GOOGLE_MAPS_API_KEY_HERE` in:
   - `driver_portal/android/app/src/main/AndroidManifest.xml`

```xml
<meta-data
    android:name="com.google.android.geo.API_KEY"
    android:value="YOUR_ACTUAL_API_KEY_HERE" />
```

### 3. Add Alarm Sound File

1. Download an alarm sound (MP3 format, 3-10 seconds)
2. Name it: `trip_alarm.mp3`
3. Place it in: `driver_portal/assets/sounds/trip_alarm.mp3`

**Free alarm sound sources:**
- https://pixabay.com/sound-effects/search/alarm/
- https://freesound.org/search/?q=alarm
- https://www.zapsplat.com/sound-effect-categories/alarm-sounds/

### 4. Build and Run

```bash
cd driver_portal
flutter clean
flutter pub get
flutter run
```

## Permissions Required

The app now requests the following permissions:

### Android Permissions (Automatically Added):
```xml
<uses-permission android:name="android.permission.ACCESS_FINE_LOCATION" />
<uses-permission android:name="android.permission.ACCESS_COARSE_LOCATION" />
<uses-permission android:name="android.permission.ACCESS_BACKGROUND_LOCATION" />
<uses-permission android:name="android.permission.INTERNET" />
```

### iOS Permissions (Add to Info.plist if needed):
```xml
<key>NSLocationWhenInUseUsageDescription</key>
<string>We need your location to show you on the map when starting trips</string>
<key>NSLocationAlwaysUsageDescription</key>
<string>We need your location for trip tracking</string>
```

## User Experience Flow

### Upcoming Trips with Countdown:

1. **Driver opens app** → Sees home screen
2. **Upcoming trips section** → Shows trips with countdown timers
3. **Timer updates** → Every minute, countdown decreases
4. **24 hours before trip** → Alert notification shown
5. **5 minutes before trip** → Alarm sound plays + urgent dialog appears
6. **Tap notification** → Opens trip details

**Example Display:**
```
🚛 Safari #TR-001
⏱️ Siku 2, Masaa 14 zilizobaki        (Blue - 2 days+ away)
Dar es Salaam → Mwanza

🚛 Safari #TR-002
⏱️ Masaa 18, Dakika 30 zilizobaki    (Orange - within 24hrs)
Arusha → Moshi

🚛 Safari #TR-003
⏱️ Dakika 45 zilizobaki               (Red - within 1 hour)
Dodoma → Morogoro
```

### Live GPS Map on Trip Confirmation:

1. **Driver taps "Start Trip"** → Opens confirmation screen
2. **App requests location permission** → Driver allows
3. **Map loads** → Shows Google Map with current location
4. **Blue marker appears** → At driver's GPS coordinates
5. **Green indicator shows** → "LIVE GPS" (location active)
6. **Driver can:**
   - Pan map to explore
   - Zoom in/out
   - Tap "My Location" button to recenter
7. **Tap "ANZA SAFARI"** → Trip starts with confirmed location

## Testing

### Test Countdown Timer:
1. Create a test trip with start time 2 days from now
2. Open driver app → Should show "Siku 2, Masaa X zilizobaki"
3. Create another trip starting in 30 minutes
4. Should show orange countdown "Dakika 30 zilizobaki"
5. Wait for time to pass → Countdown updates

### Test Alarm:
1. Create trip starting in 5 minutes
2. Wait → Alarm should play and dialog should appear
3. Verify alarm sound plays once per trip

### Test GPS Map:
1. Open app on physical device (GPS doesn't work well in emulator)
2. Tap on any trip → "Thibitisha Safari"
3. Grant location permission when requested
4. Map should load with your current location
5. Blue marker should appear at your position
6. Green "LIVE GPS" indicator should be active
7. Tap "My Location" button → Map should recenter

## Troubleshooting

### Map Not Showing:
- ✅ Check Google Maps API key is valid
- ✅ Ensure "Maps SDK for Android" is enabled in Google Cloud Console
- ✅ Verify internet connection
- ✅ Check location permissions are granted

### Alarm Not Playing:
- ✅ Ensure `trip_alarm.mp3` file exists in `assets/sounds/`
- ✅ Check volume is not muted
- ✅ Verify alarm hasn't already played for this trip

### Countdown Not Updating:
- ✅ Ensure app is in foreground
- ✅ Check trip has valid `planned_start_date`
- ✅ Verify timer is running (check logs)

### Location Not Found:
- ✅ Enable device GPS/Location services
- ✅ Grant location permission to app
- ✅ Test on physical device (not emulator)
- ✅ Ensure device has GPS signal (go outside if needed)

## Technical Architecture

### State Management:
```dart
// Home Screen State
Timer? _countdownTimer;           // Updates UI every minute
Set<String> _alarmPlayedForTrips; // Tracks which trips played alarm
AudioPlayer _audioPlayer;         // Plays alarm sound

// Confirm Trip Screen State
GoogleMapController? _mapController;
Position? _currentPosition;
Set<Marker> _markers;
bool _loadingLocation;
```

### Key Methods:

**Home Screen:**
- `_startCountdownTimer()` - Starts minute-by-minute timer
- `_checkAndPlayAlarmForApproachingTrips()` - Checks if alarm should play
- `_playAlarmSound()` - Plays alarm MP3
- `_showTripStartingAlert()` - Shows urgent dialog
- `_upcomingTripItem()` - Renders trip with countdown

**Confirm Trip Screen:**
- `_requestLocationPermission()` - Requests location access
- `_getCurrentLocation()` - Gets GPS coordinates
- `_mapSection()` - Renders Google Map

## Performance Considerations

- Timer runs every **1 minute** (not every second) to save battery
- GPS location requested **only on confirm screen** (not continuously)
- Alarm plays **once per trip** (tracked by trip ID)
- Map loads **on-demand** when confirmation screen opens
- Location updates **manually** via "My Location" button

## Future Enhancements (Optional)

- [ ] Push notifications for 24-hour alerts (even when app closed)
- [ ] Background location tracking during active trip
- [ ] Show route from current location to trip origin on map
- [ ] Multiple language support for alarm messages
- [ ] Snooze option for trip alarms
- [ ] Customizable alarm sound selection
- [ ] Battery-efficient background countdown

## Files Modified

1. `pubspec.yaml` - Added dependencies
2. `android/app/src/main/AndroidManifest.xml` - Added permissions + Maps API key
3. `lib/screens/home/home_screen.dart` - Added countdown timer + alarm
4. `lib/screens/confirm_trip_start/confirm_trip_start_screen.dart` - Added live GPS map
5. `assets/sounds/` - New directory for alarm sound

## Dependencies Added

```yaml
google_maps_flutter: ^2.5.0    # Google Maps display
geolocator: ^10.1.0            # GPS location services
permission_handler: ^11.0.1    # Permission management
audioplayers: ^5.2.1           # Audio playback
```

## Support

For issues or questions:
1. Check troubleshooting section above
2. Verify all setup steps completed
3. Check Flutter logs: `flutter logs`
4. Review Google Maps API key configuration

---

**Version:** 1.0  
**Last Updated:** February 15, 2026  
**Author:** AI Assistant
