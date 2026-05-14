# API Configuration Guide

## Local Development Setup

The mobile app is configured to use your local Laravel backend. The API base URL is automatically set based on the platform you're running on.

### Android Emulator

When running on Android emulator, the app uses:
```
http://10.0.2.2:8000/api
```

**Why?** Android emulator uses `10.0.2.2` as a special IP address that maps to your host machine's `127.0.0.1`. This is the standard way to access localhost from Android emulator.

### iOS Simulator

When running on iOS simulator, the app uses:
```
http://127.0.0.1:8000/api
```

### Physical Devices (Android/iOS)

For physical devices, you have two options:

#### Option 1: Use your local machine's IP address

1. Find your local IP address:
   - **Windows**: Run `ipconfig` in command prompt, look for "IPv4 Address"
   - **Mac/Linux**: Run `ifconfig` in terminal, look for "inet" under your network interface
   - Example: `192.168.1.100`

2. Update `mobile_app/lib/config/api_config.dart`:
   ```dart
   static const String baseUrl = 'http://192.168.1.100:8000/api';
   ```

3. Make sure your phone and computer are on the same Wi-Fi network

#### Option 2: Use ngrok or similar tunneling service

1. Install ngrok: https://ngrok.com/
2. Run: `ngrok http 8000`
3. Use the ngrok URL in the API config

### Current Configuration

The app automatically detects the platform and uses:
- **Android**: `http://10.0.2.2:8000/api` (for emulator)
- **iOS**: `http://127.0.0.1:8000/api` (for simulator)
- **Other**: `http://127.0.0.1:8000/api`

### Testing the Connection

1. Make sure Laravel backend is running:
   ```bash
   php artisan serve
   ```

2. Test the API endpoint in browser:
   ```
   http://127.0.0.1:8000/api/parent/login
   ```

3. If using Android emulator, test:
   ```
   http://10.0.2.2:8000/api/parent/login
   ```

### Troubleshooting

**Issue: "Connection timeout" or "Network error"**

1. **Android Emulator:**
   - Make sure you're using `10.0.2.2` not `127.0.0.1`
   - Verify Laravel is running on port 8000
   - Check that emulator has internet access

2. **Physical Device:**
   - Use your computer's local IP address (not 127.0.0.1)
   - Ensure device and computer are on same Wi-Fi network
   - Check firewall settings on your computer
   - Make sure Laravel is accessible from network (not just localhost)

3. **iOS Simulator:**
   - `127.0.0.1` should work
   - If not, try using your local IP address

### Production Configuration

When deploying to production, update `mobile_app/lib/config/api_config.dart`:

```dart
static const String baseUrl = 'https://yourdomain.com/api';
```

Make sure to:
- Use HTTPS in production
- Update CORS settings in Laravel
- Configure proper SSL certificates

