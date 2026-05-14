import 'package:dio/dio.dart';
import '../config/api_config.dart';
import 'api_service.dart';

class AuthService {
  final ApiService _api = ApiService();

  Future<Map<String, dynamic>> login(String phone, String password) async {
    try {
      final response = await _api.post(ApiConfig.driverLogin, data: {
        'phone': phone.trim(),
        'password': password,
      });
      final data = response.data is Map ? Map<String, dynamic>.from(response.data as Map) : <String, dynamic>{};
      if (data['success'] == true && data['data'] != null) {
        final dataMap = Map<String, dynamic>.from(data['data'] as Map);
        if (dataMap['token'] != null) {
          await _api.saveToken(dataMap['token'].toString());
          return data;
        }
      }
      return {
        'success': false,
        'message': data['message']?.toString() ?? 'Login failed.',
      };
    } on DioException catch (e) {
      String msg = _loginErrorMessage(e);
      return {'success': false, 'message': msg};
    } catch (e) {
      final s = e.toString();
      if (s.contains('SocketException') || s.contains('Connection') || s.contains('Failed host')) {
        return {
          'success': false,
          'message': 'Cannot reach server. On phone? Tap version 5 times on login to set Server URL (your PC IP).',
        };
      }
      return {'success': false, 'message': s.length > 100 ? 'Connection error. Set Server URL (tap version 5x).' : s};
    }
  }

  static String _loginErrorMessage(DioException e) {
    if (e.response != null) {
      final d = e.response!.data;
      if (d is Map && d.containsKey('message')) {
        final m = d['message'].toString();
        if (m.isNotEmpty) return m;
      }
      final code = e.response!.statusCode ?? 0;
      if (code >= 500) {
        return 'Server error. Ensure Laravel is running and driver API is available. On phone? Set Server URL (tap version 5x).';
      }
      if (code == 401 || code == 404) {
        return 'Invalid phone number or password.';
      }
      return 'Request failed ($code). Try again.';
    }
    if (e.type == DioExceptionType.connectionTimeout ||
        e.type == DioExceptionType.connectionError ||
        e.type == DioExceptionType.unknown) {
      return 'Cannot reach server. On phone? Tap version 5 times to set Server URL (your PC IP, e.g. http://192.168.1.5:8000/api).';
    }
    return 'Login failed. Try again.';
  }

  Future<Map<String, dynamic>> logout() async {
    try {
      await _api.post(ApiConfig.driverLogout);
    } catch (_) {}
    await _api.removeToken();
    return {'success': true};
  }

  Future<Map<String, dynamic>> me() async {
    try {
      final response = await _api.get(ApiConfig.driverMe);
      final data = response.data is Map ? response.data as Map : null;
      return {'success': true, 'data': data?['data']};
    } catch (e) {
      return {'success': false, 'message': e.toString()};
    }
  }

  Future<bool> isAuthenticated() async {
    final token = await _api.getToken();
    return token != null && token.isNotEmpty;
  }

  Future<Map<String, dynamic>> changePassword({
    required String currentPassword,
    required String newPassword,
    required String confirmPassword,
  }) async {
    try {
      await _api.put(ApiConfig.driverChangePassword, data: {
        'current_password': currentPassword,
        'password': newPassword,
        'password_confirmation': confirmPassword,
      });
      return {'success': true, 'message': 'Password changed successfully'};
    } on DioException catch (e) {
      String msg = 'Failed to change password.';
      if (e.response?.data is Map && (e.response!.data as Map).containsKey('message')) {
        msg = (e.response!.data as Map)['message'].toString();
      }
      return {'success': false, 'message': msg};
    }
  }

  Future<Map<String, dynamic>> forgotPassword(String phone) async {
    try {
      final response = await _api.post(ApiConfig.driverForgotPassword, data: {'phone': phone.trim()});
      final data = response.data is Map ? Map<String, dynamic>.from(response.data as Map) : <String, dynamic>{};
      return {'success': data['success'] == true, 'message': data['message']?.toString()};
    } on DioException catch (e) {
      String msg = 'Failed to send OTP.';
      if (e.response?.data is Map && (e.response!.data as Map).containsKey('message')) {
        msg = (e.response!.data as Map)['message'].toString();
      }
      return {'success': false, 'message': msg};
    }
  }

  Future<Map<String, dynamic>> verifyOtp(String phone, String code) async {
    try {
      final response = await _api.post(ApiConfig.driverVerifyOtp, data: {
        'phone': phone.trim(),
        'code': code.trim(),
      });
      final data = response.data is Map ? Map<String, dynamic>.from(response.data as Map) : <String, dynamic>{};
      if (data['success'] == true && data['data'] != null) {
        final dataMap = Map<String, dynamic>.from(data['data'] as Map);
        return {'success': true, 'reset_token': dataMap['reset_token']?.toString()};
      }
      return {'success': false, 'message': data['message']?.toString() ?? 'Invalid OTP.'};
    } on DioException catch (e) {
      String msg = 'Invalid or expired OTP.';
      if (e.response?.data is Map && (e.response!.data as Map).containsKey('message')) {
        msg = (e.response!.data as Map)['message'].toString();
      }
      return {'success': false, 'message': msg};
    }
  }

  Future<Map<String, dynamic>> resetPassword({
    required String phone,
    required String password,
    required String passwordConfirmation,
    required String resetToken,
  }) async {
    _api.setTempToken(resetToken);
    try {
      final response = await _api.post(ApiConfig.driverResetPassword, data: {
        'phone': phone.trim(),
        'password': password,
        'password_confirmation': passwordConfirmation,
      });
      final data = response.data is Map ? Map<String, dynamic>.from(response.data as Map) : <String, dynamic>{};
      return {'success': data['success'] == true, 'message': data['message']?.toString()};
    } on DioException catch (e) {
      String msg = 'Failed to reset password.';
      if (e.response?.data is Map && (e.response!.data as Map).containsKey('message')) {
        msg = (e.response!.data as Map)['message'].toString();
      }
      return {'success': false, 'message': msg};
    } catch (e) {
      return {'success': false, 'message': e.toString()};
    }
  }
}
