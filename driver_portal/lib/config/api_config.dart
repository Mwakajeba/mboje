/// API base URL. On Android physical device, set Server URL (tap version 5x on login)
/// so the app can reach your PC (e.g. http://192.168.1.100:8000/api).

import 'package:flutter/foundation.dart' show kIsWeb, kReleaseMode;
import 'dart:io' show Platform;
import 'package:shared_preferences/shared_preferences.dart';

class ApiConfig {
  static const String _prefKey = 'driver_portal_api_base_url';

  static String? _baseUrl;
  static bool _initialized = false;

  /// Call before ApiService().init(). Loads saved URL so Android phone can use your PC IP.
  static Future<void> ensureInitialized() async {
    if (_initialized) return;
    _initialized = true;
    try {
      final prefs = await SharedPreferences.getInstance();
      final saved = prefs.getString(_prefKey);
      if (saved != null && saved.trim().isNotEmpty) {
        _baseUrl = saved.trim().replaceFirst(RegExp(r'/+\s*$'), '');
        return;
      }
    } catch (_) {}
    _baseUrl = _defaultUrl();
  }

  static String _defaultUrl() {
    if (kIsWeb) return 'http://127.0.0.1:8000/api';
    if (Platform.isAndroid) {
      // Emulator: 10.0.2.2 is the host PC. For physical device, set URL in app (tap version 5x).
      return 'http://10.0.2.2:8000/api';
    }
    if (Platform.isIOS) {
      return kReleaseMode ? 'https://demo.smartsoft.co.tz/api' : 'http://127.0.0.1:8000/api';
    }
    return 'http://127.0.0.1:8000/api';
  }

  static String get baseUrl => _baseUrl ?? _defaultUrl();

  /// Save custom base URL (e.g. http://192.168.1.100:8000/api for Android physical device).
  static Future<void> setOverrideUrl(String url) async {
    String u = url.trim();
    if (u.isEmpty) return;
    if (!u.endsWith('/api')) u = u.replaceFirst(RegExp(r'/+$'), '') + '/api';
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_prefKey, u);
    _baseUrl = u;
  }

  static Future<String?> getOverrideUrl() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      return prefs.getString(_prefKey);
    } catch (_) {
      return null;
    }
  }

  // Driver auth
  static const String driverLogin = '/driver/login';
  static const String driverLogout = '/driver/logout';
  static const String driverMe = '/driver/me';
  static const String driverForgotPassword = '/driver/forgot-password';
  static const String driverVerifyOtp = '/driver/verify-otp';
  static const String driverResetPassword = '/driver/reset-password';
  static const String driverChangePassword = '/driver/change-password';
  
  // Driver trips
  static const String driverTrips = '/driver/trips';
  static const String driverUpcomingTrips = '/driver/trips/upcoming';
  static const String driverActiveTrip = '/driver/trips/active';
}
