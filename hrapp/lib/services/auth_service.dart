import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import '../config/api_config.dart';

class AuthService {
  static const _storage = FlutterSecureStorage();
  static const String _tokenKey = 'auth_token';
  static const String _userKey = 'user_data';
  static const String _branchKey = 'selected_branch_id';
  static const String _locationKey = 'selected_location_id';

  // Helper method to format network errors
  static String _formatNetworkError(dynamic error) {
    final errorString = error.toString().toLowerCase();
    
    if (errorString.contains('failed host lookup') || 
        errorString.contains('connection refused') ||
        errorString.contains('socketexception') ||
        errorString.contains('network is unreachable')) {
      return 'Cannot connect to server. Please check:\n'
          '1. Laravel server is running (php artisan serve)\n'
          '2. API URL in api_config.dart is correct\n'
          '3. For Android Emulator: Use http://10.0.2.2:8000/api\n'
          '4. For iOS Simulator: Use http://localhost:8000/api\n'
          '5. For Physical Device: Use your computer IP (e.g., http://192.168.1.XXX:8000/api)\n'
          '   Find your IP: Windows (ipconfig) or Mac/Linux (ifconfig)';
    } else if (errorString.contains('timeout')) {
      return 'Connection timeout. Please check your network connection and try again.';
    } else {
      return 'Network error: ${error.toString()}';
    }
  }

  // Persist selected branch/location context
  static Future<void> setSelectedContext({
    required int branchId,
    int? locationId,
  }) async {
    await _storage.write(key: _branchKey, value: branchId.toString());
    if (locationId != null) {
      await _storage.write(key: _locationKey, value: locationId.toString());
    } else {
      await _storage.delete(key: _locationKey);
    }

    // Also reflect the choice into stored user object for convenience
    final userJson = await _storage.read(key: _userKey);
    if (userJson != null) {
      try {
        final map = jsonDecode(userJson) as Map<String, dynamic>;
        map['branch_id'] = branchId;
        map['location_id'] = locationId;
        await _storage.write(key: _userKey, value: jsonEncode(map));
      } catch (_) {}
    }
  }

  static Future<int?> getSelectedBranchId() async {
    final s = await _storage.read(key: _branchKey);
    if (s == null || s.isEmpty) return null;
    return int.tryParse(s);
  }

  static Future<int?> getSelectedLocationId() async {
    final s = await _storage.read(key: _locationKey);
    if (s == null || s.isEmpty) return null;
    return int.tryParse(s);
  }

  /// Get selected branch and location context as a map
  static Future<Map<String, dynamic>> getSelectedContext() async {
    final branchId = await getSelectedBranchId();
    final locationId = await getSelectedLocationId();
    return {
      'branch_id': branchId,
      'location_id': locationId,
    };
  }

  static Future<Map<String, String>> _authHeaders() async {
    final headers = <String, String>{
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    };
    final token = await getToken();
    if (token != null && token.isNotEmpty) {
      headers['Authorization'] = 'Bearer $token';
    }
    // Attach branch/location context if available
    final branchId = await getSelectedBranchId();
    final locationId = await getSelectedLocationId();
    if (branchId != null) headers['X-Branch-Id'] = branchId.toString();
    if (locationId != null) headers['X-Location-Id'] = locationId.toString();
    return headers;
  }

  // Login with phone and password
  static Future<Map<String, dynamic>> login(String phone, String password) async {
    try {
      final url = ApiConfig.getUrl(ApiConfig.login);
      final response = await http.post(
        Uri.parse(url),
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: jsonEncode({
          'phone': phone,
          'password': password,
        }),
      ).timeout(
        const Duration(seconds: 30),
        onTimeout: () {
          throw Exception('Connection timeout. Please check your network and API URL.');
        },
      );

      if (response.statusCode == 0 || response.body.isEmpty) {
        return {
          'success': false,
          'message': _formatNetworkError('Connection failed'),
        };
      }

      Map<String, dynamic> data;
      try {
        data = jsonDecode(response.body);
      } catch (e) {
        return {
          'success': false,
          'message': 'Invalid server response. Please check if the server is running correctly.',
        };
      }

      if (response.statusCode == 200 && data['success'] == true) {
        // Store token and user data
        await _storage.write(key: _tokenKey, value: data['data']['token']);
        await _storage.write(
          key: _userKey,
          value: jsonEncode(data['data']['user']),
        );

        return {
          'success': true,
          'message': data['message'],
          'user': data['data']['user'],
        };
      } else {
        return {
          'success': false,
          'message': data['message'] ?? 'Login failed',
        };
      }
    } catch (e) {
      return {
        'success': false,
        'message': _formatNetworkError(e),
      };
    }
  }

  // Logout
  static Future<Map<String, dynamic>> logout() async {
    try {
      final token = await getToken();
      if (token == null) {
        await clearAuth();
        return {'success': true, 'message': 'Logged out successfully'};
      }

      final response = await http.post(
        Uri.parse(ApiConfig.getUrl(ApiConfig.logout)),
        headers: await _authHeaders(),
      );

      Map<String, dynamic> data;
      try {
        data = jsonDecode(response.body);
      } catch (e) {
        await clearAuth();
        return {
          'success': true,
          'message': 'Logged out successfully',
        };
      }

      await clearAuth();

      return {
        'success': data['success'] ?? true,
        'message': data['message'] ?? 'Logged out successfully',
      };
    } catch (e) {
      await clearAuth();
      return {
        'success': true,
        'message': 'Logged out successfully',
      };
    }
  }

  // Get current user
  static Future<Map<String, dynamic>?> getCurrentUser() async {
    try {
      final userJson = await _storage.read(key: _userKey);
      if (userJson != null) {
        return jsonDecode(userJson);
      }
      return null;
    } catch (e) {
      return null;
    }
  }

  // Get auth token
  static Future<String?> getToken() async {
    return await _storage.read(key: _tokenKey);
  }

  // Check if user is logged in
  static Future<bool> isLoggedIn() async {
    final token = await getToken();
    return token != null && token.isNotEmpty;
  }

  // Clear auth data
  static Future<void> clearAuth() async {
    await _storage.delete(key: _tokenKey);
    await _storage.delete(key: _userKey);
    await _storage.delete(key: _branchKey);
    await _storage.delete(key: _locationKey);
  }

  // Forgot password - request OTP
  static Future<Map<String, dynamic>> forgotPassword(String phone) async {
    try {
      final response = await http.post(
        Uri.parse(ApiConfig.getUrl(ApiConfig.forgotPassword)),
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: jsonEncode({
          'phone': phone,
        }),
      ).timeout(
        const Duration(seconds: 30),
        onTimeout: () {
          throw Exception('Connection timeout');
        },
      );

      final data = jsonDecode(response.body);

      if (response.statusCode == 200 && data['success'] == true) {
        return {
          'success': true,
          'message': data['message'],
          'phone': data['data']['phone'],
        };
      } else {
        return {
          'success': false,
          'message': data['message'] ?? 'Failed to send OTP',
        };
      }
    } catch (e) {
      return {
        'success': false,
        'message': _formatNetworkError(e),
      };
    }
  }

  // Verify OTP
  static Future<Map<String, dynamic>> verifyOtp(String phone, String code) async {
    try {
      final response = await http.post(
        Uri.parse(ApiConfig.getUrl(ApiConfig.verifyOtp)),
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: jsonEncode({
          'phone': phone,
          'code': code,
        }),
      ).timeout(
        const Duration(seconds: 30),
        onTimeout: () {
          throw Exception('Connection timeout');
        },
      );

      final data = jsonDecode(response.body);

      if (response.statusCode == 200 && data['success'] == true) {
        // Store reset token temporarily
        await _storage.write(
          key: 'reset_token',
          value: data['data']['reset_token'],
        );
        await _storage.write(
          key: 'reset_phone',
          value: phone,
        );

        return {
          'success': true,
          'message': data['message'],
        };
      } else {
        return {
          'success': false,
          'message': data['message'] ?? 'Invalid OTP',
        };
      }
    } catch (e) {
      return {
        'success': false,
        'message': _formatNetworkError(e),
      };
    }
  }

  // Reset password
  static Future<Map<String, dynamic>> resetPassword(
    String phone,
    String password,
    String passwordConfirmation,
  ) async {
    try {
      final response = await http.post(
        Uri.parse(ApiConfig.getUrl(ApiConfig.resetPassword)),
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: jsonEncode({
          'phone': phone,
          'password': password,
          'password_confirmation': passwordConfirmation,
        }),
      ).timeout(
        const Duration(seconds: 30),
        onTimeout: () {
          throw Exception('Connection timeout');
        },
      );

      final data = jsonDecode(response.body);

      if (response.statusCode == 200 && data['success'] == true) {
        // Clear reset token
        await _storage.delete(key: 'reset_token');
        await _storage.delete(key: 'reset_phone');

        return {
          'success': true,
          'message': data['message'],
        };
      } else {
        return {
          'success': false,
          'message': data['message'] ?? 'Failed to reset password',
        };
      }
    } catch (e) {
      return {
        'success': false,
        'message': _formatNetworkError(e),
      };
    }
  }

  // Update profile
  static Future<Map<String, dynamic>> updateProfile({
    required String name,
    required String phone,
    String? email,
  }) async {
    try {
      final token = await getToken();
      if (token == null) {
        return {
          'success': false,
          'message': 'Not authenticated',
        };
      }

      final response = await http.put(
        Uri.parse(ApiConfig.getUrl(ApiConfig.updateProfile)),
        headers: await _authHeaders(),
        body: jsonEncode({
          'name': name,
          'phone': phone,
          'email': email,
        }),
      ).timeout(
        const Duration(seconds: 30),
        onTimeout: () {
          throw Exception('Connection timeout');
        },
      );

      final data = jsonDecode(response.body);

      if (response.statusCode == 200 && data['success'] == true) {
        // Update stored user data
        await _storage.write(
          key: _userKey,
          value: jsonEncode(data['data']),
        );

        return {
          'success': true,
          'message': data['message'],
          'user': data['data'],
        };
      } else {
        return {
          'success': false,
          'message': data['message'] ?? 'Failed to update profile',
        };
      }
    } catch (e) {
      return {
        'success': false,
        'message': _formatNetworkError(e),
      };
    }
  }

  // Change password
  static Future<Map<String, dynamic>> changePassword({
    required String currentPassword,
    required String newPassword,
    required String newPasswordConfirmation,
  }) async {
    try {
      final token = await getToken();
      if (token == null) {
        return {
          'success': false,
          'message': 'Not authenticated',
        };
      }

      final response = await http.put(
        Uri.parse(ApiConfig.getUrl(ApiConfig.changePassword)),
        headers: await _authHeaders(),
        body: jsonEncode({
          'current_password': currentPassword,
          'password': newPassword,
          'password_confirmation': newPasswordConfirmation,
        }),
      ).timeout(
        const Duration(seconds: 30),
        onTimeout: () {
          throw Exception('Connection timeout');
        },
      );

      final data = jsonDecode(response.body);

      if (response.statusCode == 200 && data['success'] == true) {
        return {
          'success': true,
          'message': data['message'],
        };
      } else {
        return {
          'success': false,
          'message': data['message'] ?? 'Failed to change password',
        };
      }
    } catch (e) {
      return {
        'success': false,
        'message': _formatNetworkError(e),
      };
    }
  }
}

