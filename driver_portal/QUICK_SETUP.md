# Quick Setup Guide - Countdown Timer & GPS Features

## ⚡ Fast Setup (5 Minutes)

### Step 1: Install Dependencies
```bash
cd driver_portal
flutter pub get
```

### Step 2: Add Google Maps API Key

1. Get API key from: https://console.cloud.google.com/apis/credentials
2. Enable **Maps SDK for Android**
3. Edit `android/app/src/main/AndroidManifest.xml`
4. Replace `YOUR_GOOGLE_MAPS_API_KEY_HERE` with your actual key

```xml
<meta-data
    android:name="com.google.android.geo.API_KEY"
    android:value="AIzaSyAbc123..." />  <!-- PUT YOUR KEY HERE -->
```

### Step 3: Add Alarm Sound

1. Download an alarm MP3 (3-10 seconds)
2. Rename it to: `trip_alarm.mp3`
3. Save to: `driver_portal/assets/sounds/trip_alarm.mp3`

**Quick download:** https://pixabay.com/sound-effects/search/alarm/

### Step 4: Build and Run
```bash
flutter clean
flutter pub get
flutter run
```

## ✅ What You'll See

### 1. Home Screen - Countdown Timers
```
Safari Zinazokuja (Upcoming Trips)
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
🚛 Safari #TR-001
   Leo, Saa 16:30
   ⏱️ Masaa 5, Dakika 30 zilizobaki  🟠
   Dar es Salaam → Mwanza
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
🚛 Safari #TR-002  
   Kesho, Saa 08:00
   ⏱️ Siku 1, Masaa 15 zilizobaki  🔵
   Arusha → Moshi
```

### 2. Confirm Trip - Live GPS Map
```
┌─────────────────────────────┐
│  [LIVE GPS 🟢]              │
│                             │
│    📍                       │
│   /│\    ← You are here    │
│    │                        │
│  [MAP]                      │
│                             │
│               [📍My Location]│
└─────────────────────────────┘
```

## 🎯 Quick Test

### Test Countdown:
1. Open app
2. Go to "Safari Zinazokuja"
3. You should see countdown like: **"Siku 2, Masaa 14 zilizobaki"**

### Test Alarm:
1. Create trip starting in 5 minutes
2. Wait...
3. App plays alarm sound and shows dialog automatically

### Test GPS Map:
1. Tap any trip → "Thibitisha Safari"
2. Grant location permission
3. See your live location on Google Map

## 🔧 Troubleshooting

| Problem | Solution |
|---------|----------|
| Map not showing | Check API key is correct |
| No alarm sound | Add `trip_alarm.mp3` to `assets/sounds/` |
| No location | Enable GPS on device + grant permission |
| Build errors | Run `flutter clean && flutter pub get` |

## 📱 Test on Real Device

**Important:** GPS features work best on physical devices, not emulators!

```bash
# Connect Android phone via USB
flutter devices
flutter run
```

## 🎉 You're Done!

The app now has:
- ✅ Real-time countdown timers
- ✅ Alarm sounds for approaching trips
- ✅ Live GPS map with current location
- ✅ Auto-updating every minute

---

**Need help?** Check `COUNTDOWN_AND_GPS_FEATURES.md` for detailed documentation.
