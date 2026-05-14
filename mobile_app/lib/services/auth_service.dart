import 'dart:convert';
import 'package:dio/dio.dart';
import '../config/api_config.dart';
import '../utils/phone_helper.dart';
import 'api_service.dart';

/// Authentication Service
/// 
/// Handles all authentication-related API calls
class AuthService {
  final ApiService _apiService = ApiService();

  /// Login
  /// 
  /// Returns a map with 'success', 'message', and 'data' keys
  /// Simple login - sends phone and password as-is to backend
  Future<Map<String, dynamic>> login(String phone, String password) async {
    // Send phone number as entered - backend will handle format matching
    return await _tryLogin(phone.trim(), password);
  }
  
  /// Internal method to attempt login with a specific phone format
  Future<Map<String, dynamic>> _tryLogin(String phoneFormat, String password) async {
    try {
      print('=== LOGIN ATTEMPT ===');
      print('Phone: $phoneFormat');
      print('API URL: ${ApiConfig.baseUrl}${ApiConfig.parentLogin}');
      
      final response = await _apiService.post(
        ApiConfig.parentLogin,
        data: {
          'phone': phoneFormat,
          'password': password,
        },
      );

      print('=== LOGIN RESPONSE ===');
      print('Status Code: ${response.statusCode}');
      print('Response headers: ${response.headers}');
      print('Response data type: ${response.data.runtimeType}');
      print('Response data: ${response.data}');
      
      final responseData = response.data;
      
      // Check if response is HTML/JavaScript (error page) instead of JSON
      if (responseData is String) {
        final responseString = responseData.toString();
        if (responseString.contains('<!DOCTYPE') || 
            responseString.contains('<html') || 
            responseString.contains('syntax error') ||
            responseString.contains('unexpected token')) {
          print('ERROR: Server returned HTML/JavaScript instead of JSON');
          return {
            'success': false,
            'message': 'Server returned an error page. Please check if the API server is running and the endpoint is correct.',
          };
        }
      }
      
      // Check if response is valid and convert to Map<String, dynamic>
      if (responseData is! Map) {
        print('ERROR: Response is not a Map. Type: ${responseData.runtimeType}');
        return {
          'success': false,
          'message': 'Invalid response format from server. Please try again.',
        };
      }
      
      // Convert to Map<String, dynamic>
      final data = Map<String, dynamic>.from(responseData as Map);
      
      if (data['success'] == true) {
        // Check if token exists
        if (data['data'] != null && data['data'] is Map) {
          final dataMap = Map<String, dynamic>.from(data['data'] as Map);
          if (dataMap['token'] != null) {
            // Save token
            await _apiService.saveToken(dataMap['token'].toString());
            print('Token saved successfully');
            // Ensure we return Map<String, dynamic>
            return Map<String, dynamic>.from(data);
          } else {
            return {
              'success': false,
              'message': data['message'] ?? 'Login successful but no token received. Please try again.',
            };
          }
        } else {
          return {
            'success': false,
            'message': data['message'] ?? 'Login successful but no token received. Please try again.',
          };
        }
      } else {
        return {
          'success': false,
          'message': data['message']?.toString() ?? 'Login failed. Please check your credentials.',
        };
      }
    } on DioException catch (e) {
      String errorMessage = 'Login failed. Please try again.';
      
      if (e.response != null) {
        // Get error message from response
        final responseData = e.response!.data;
        if (responseData is Map<String, dynamic>) {
          if (responseData.containsKey('message')) {
            errorMessage = responseData['message'].toString();
          } else if (responseData.containsKey('error')) {
            errorMessage = responseData['error'].toString();
          }
        }
        
        // Handle specific status codes
        if (e.response!.statusCode == 401) {
          errorMessage = responseData is Map && responseData.containsKey('message')
              ? responseData['message'].toString()
              : 'Invalid phone number or password. Please check your credentials.';
        } else if (e.response!.statusCode == 429) {
          errorMessage = responseData is Map && responseData.containsKey('message')
              ? responseData['message'].toString()
              : 'Too many login attempts. Please try again later.';
        } else if (e.response!.statusCode == 403) {
          errorMessage = responseData is Map && responseData.containsKey('message')
              ? responseData['message'].toString()
              : 'Access denied. Please contact support.';
        } else if (e.response!.statusCode == 422) {
          // Validation errors
          if (responseData is Map && responseData.containsKey('errors')) {
            final errors = responseData['errors'] as Map<String, dynamic>;
            final firstError = errors.values.first;
            if (firstError is List && firstError.isNotEmpty) {
              errorMessage = firstError.first.toString();
            }
          }
        } else if (e.response!.statusCode == 500) {
          errorMessage = responseData is Map && responseData.containsKey('message')
              ? responseData['message'].toString()
              : 'Server error occurred. Please try again later or contact support.';
        } else if (e.response!.statusCode != null) {
          // Try to get message from response
          if (responseData is Map && responseData.containsKey('message')) {
            errorMessage = responseData['message'].toString();
          } else {
            errorMessage = 'Server error (${e.response!.statusCode}). Please try again.';
          }
        }
      } else if (e.type == DioExceptionType.connectionTimeout) {
        errorMessage = 'Connection timeout. Please check your internet connection and try again.';
      } else if (e.type == DioExceptionType.receiveTimeout) {
        errorMessage = 'Request timeout. The server took too long to respond. Please try again.';
      } else if (e.type == DioExceptionType.connectionError) {
        errorMessage = 'Connection error. Please check your internet connection and ensure the server is running.';
      } else if (e.type == DioExceptionType.sendTimeout) {
        errorMessage = 'Send timeout. Please check your internet connection.';
      }
      
      // Log error for debugging
      print('=== LOGIN ERROR ===');
      print('Error type: ${e.type}');
      print('Error message: ${e.message}');
      if (e.response != null) {
        print('Response status: ${e.response!.statusCode}');
        print('Response data: ${e.response!.data}');
        print('Response data type: ${e.response!.data.runtimeType}');
      } else {
        print('No response - connection error');
      }
      print('Final error message: $errorMessage');
      
      return {
        'success': false,
        'message': errorMessage,
      };
    } catch (e, stackTrace) {
      // Log unexpected errors with full details
      print('Unexpected login error: ${e.toString()}');
      print('Error type: ${e.runtimeType}');
      print('Stack trace: $stackTrace');
      
      // Try to extract a meaningful error message
      String errorMessage = 'An unexpected error occurred. Please try again.';
      
      if (e is TypeError) {
        errorMessage = 'Data format error. Please check your connection and try again.';
      } else if (e is FormatException) {
        errorMessage = 'Invalid response from server. Please try again.';
      } else if (e.toString().contains('SocketException') || e.toString().contains('Failed host lookup')) {
        errorMessage = 'Cannot connect to server. Please check your internet connection and ensure the server is running.';
      } else if (e.toString().contains('HandshakeException')) {
        errorMessage = 'SSL/TLS error. Please check your connection.';
      } else {
        errorMessage = 'Error: ${e.toString()}. Please try again or contact support.';
      }
      
      return {
        'success': false,
        'message': errorMessage,
      };
    }
  }

  /// Logout
  Future<Map<String, dynamic>> logout() async {
    try {
      await _apiService.post(ApiConfig.parentLogout);
      await _apiService.removeToken();
      return {'success': true, 'message': 'Logged out successfully'};
    } catch (e) {
      await _apiService.removeToken(); // Remove token even if API call fails
      return {'success': true, 'message': 'Logged out'};
    }
  }

  /// Get current user information
  Future<Map<String, dynamic>> getCurrentUser() async {
    try {
      final response = await _apiService.get(ApiConfig.parentMe);
      return {
        'success': true,
        'data': response.data['data'],
      };
    } catch (e) {
      return {
        'success': false,
        'message': 'Failed to get user information',
      };
    }
  }

  /// Update profile
  Future<Map<String, dynamic>> updateProfile(Map<String, dynamic> data) async {
    try {
      final response = await _apiService.put(ApiConfig.parentProfile, data: data);
      return {
        'success': true,
        'data': response.data['data'],
        'message': response.data['message'] ?? 'Profile updated successfully',
      };
    } catch (e) {
      return {
        'success': false,
        'message': 'Failed to update profile',
      };
    }
  }

  /// Change password
  Future<Map<String, dynamic>> changePassword(
    String currentPassword,
    String newPassword,
    String confirmPassword,
  ) async {
    try {
      final response = await _apiService.put(
        ApiConfig.parentChangePassword,
        data: {
          'current_password': currentPassword,
          'new_password': newPassword,
          'new_password_confirmation': confirmPassword,
        },
      );
      return {
        'success': true,
        'message': response.data['message'] ?? 'Password changed successfully',
      };
    } on DioException catch (e) {
      return {
        'success': false,
        'message': e.response?.data['message'] ?? 'Failed to change password',
      };
    } catch (e) {
      return {
        'success': false,
        'message': 'Failed to change password',
      };
    }
  }

  /// Check if user is authenticated
  Future<bool> isAuthenticated() async {
    final token = await _apiService.getToken();
    return token != null && token.isNotEmpty;
  }

  /// Get token
  Future<String?> getToken() async {
    return await _apiService.getToken();
  }
}

