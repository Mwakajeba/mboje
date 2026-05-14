# API URL Configuration Guide

## For Local Development

The API URL needs to be configured based on how you're running the Flutter app:

### 1. Android Emulator
Use: `http://10.0.2.2:8000/api`
- This is the special IP that Android emulator uses to access your computer's localhost

### 2. iOS Simulator
Use: `http://localhost:8000/api` or `http://127.0.0.1:8000/api`
- iOS Simulator can access localhost directly

### 3. Physical Device (Android/iOS)
Use your computer's local IP address: `http://YOUR_IP:8000/api`

**To find your IP address:**
- **Windows**: Open Command Prompt and run `ipconfig`, look for "IPv4 Address"
- **Mac/Linux**: Open Terminal and run `ifconfig` or `ip addr`, look for "inet" address

Example: `http://192.168.1.100:8000/api`

### 4. How to Update

Edit `hrapp/lib/config/api_config.dart` and change the `baseUrl`:

```dart
static const String baseUrl = 'http://10.0.2.2:8000/api';  // Android Emulator
// OR
static const String baseUrl = 'http://localhost:8000/api';  // iOS Simulator
// OR
static const String baseUrl = 'http://192.168.1.XXX:8000/api';  // Physical Device
```

## Important Notes

1. **Make sure Laravel server is running:**
   ```bash
   php artisan serve
   ```
   This should start the server on `http://127.0.0.1:8000`

2. **Check CORS settings** in Laravel if you get CORS errors:
   - Make sure `config/cors.php` allows your Flutter app origin
   - Or use `php artisan serve --host=0.0.0.0` to allow all connections

3. **Firewall**: If using a physical device, make sure your firewall allows connections on port 8000

4. **Network**: Make sure your device/emulator and computer are on the same network (for physical devices)

