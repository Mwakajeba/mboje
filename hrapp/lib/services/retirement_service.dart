import 'dart:convert';
import 'package:http/http.dart' as http;
import 'dart:async';
import '../config/api_config.dart';
import 'auth_service.dart';

/// Service for managing Retirement requests
/// Provides methods to create, view, and manage retirement requests
class RetirementService {
  /// Get retirement requests list with optional status filter
  /// 
  /// [status] - Optional status filter: 'all', 'pending', 'checked', 'approved', 'rejected'
  /// 
  /// Returns Map with:
  /// - success: bool
  /// - data: List of retirement requests
  /// - stats: Statistics object
  /// - message: Error message if success is false
  static Future<Map<String, dynamic>> getRetirementRequests({String? status}) async {
    try {
      final token = await AuthService.getToken();
      final context = await AuthService.getSelectedContext();
      
      if (token == null) {
        return {
          'success': false,
          'message': 'Not authenticated. Please login again.',
          'error_code': 'AUTH_REQUIRED',
        };
      }

      String url = ApiConfig.getUrl(ApiConfig.retirementList);
      if (status != null && status != 'all') {
        url += '?status=${Uri.encodeComponent(status)}';
      }

      final response = await http.get(
        Uri.parse(url),
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
          'X-Branch-Id': context['branch_id']?.toString() ?? '',
          'X-Location-Id': context['location_id']?.toString() ?? '',
        },
      ).timeout(
        const Duration(seconds: 30),
        onTimeout: () {
          throw TimeoutException('Request timeout. Please check your internet connection.');
        },
      );

      final data = json.decode(response.body);

      if (response.statusCode == 200 && data['success'] == true) {
        return {
          'success': true,
          'data': data['data'] ?? [],
          'stats': data['stats'] ?? {},
        };
      } else {
        return _handleError(null, response);
      }
    } catch (e) {
      return _handleError(e, null);
    }
  }

  /// Get retirement request details
  /// 
  /// [id] - Retirement request ID
  /// 
  /// Returns Map with:
  /// - success: bool
  /// - data: Retirement details object
  /// - message: Error message if success is false
  static Future<Map<String, dynamic>> getRetirementDetails(int id) async {
    try {
      final token = await AuthService.getToken();
      final context = await AuthService.getSelectedContext();
      
      if (token == null) {
        return {
          'success': false,
          'message': 'Not authenticated. Please login again.',
          'error_code': 'AUTH_REQUIRED',
        };
      }

      final response = await http.get(
        Uri.parse(ApiConfig.getUrl(ApiConfig.retirementDetails(id))),
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
          'X-Branch-Id': context['branch_id']?.toString() ?? '',
          'X-Location-Id': context['location_id']?.toString() ?? '',
        },
      ).timeout(
        const Duration(seconds: 30),
        onTimeout: () {
          throw TimeoutException('Request timeout. Please check your internet connection.');
        },
      );

      final data = json.decode(response.body);

      if (response.statusCode == 200 && data['success'] == true) {
        return {
          'success': true,
          'data': data['data'],
        };
      } else {
        return _handleError(null, response);
      }
    } catch (e) {
      return _handleError(e, null);
    }
  }

  /// Get imprest requests eligible for retirement
  /// 
  /// Returns Map with:
  /// - success: bool
  /// - data: List of eligible imprest requests
  /// - message: Error message if success is false
  static Future<Map<String, dynamic>> getEligibleImprestForRetirement() async {
    try {
      final token = await AuthService.getToken();
      final context = await AuthService.getSelectedContext();
      
      if (token == null) {
        return {
          'success': false,
          'message': 'Not authenticated. Please login again.',
          'error_code': 'AUTH_REQUIRED',
        };
      }

      final response = await http.get(
        Uri.parse(ApiConfig.getUrl(ApiConfig.eligibleImprestForRetirement)),
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
          'X-Branch-Id': context['branch_id']?.toString() ?? '',
          'X-Location-Id': context['location_id']?.toString() ?? '',
        },
      ).timeout(
        const Duration(seconds: 30),
        onTimeout: () {
          throw TimeoutException('Request timeout. Please check your internet connection.');
        },
      );

      final data = json.decode(response.body);

      if (response.statusCode == 200 && data['success'] == true) {
        return {
          'success': true,
          'data': data['data'] ?? [],
        };
      } else {
        return _handleError(null, response);
      }
    } catch (e) {
      return _handleError(e, null);
    }
  }

  /// Get expense accounts for retirement items
  /// 
  /// Returns Map with:
  /// - success: bool
  /// - data: List of expense accounts
  /// - message: Error message if success is false
  static Future<Map<String, dynamic>> getExpenseAccounts() async {
    try {
      final token = await AuthService.getToken();
      final context = await AuthService.getSelectedContext();
      
      if (token == null) {
        return {
          'success': false,
          'message': 'Not authenticated. Please login again.',
          'error_code': 'AUTH_REQUIRED',
        };
      }

      final response = await http.get(
        Uri.parse(ApiConfig.getUrl(ApiConfig.expenseAccounts)),
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
          'X-Branch-Id': context['branch_id']?.toString() ?? '',
          'X-Location-Id': context['location_id']?.toString() ?? '',
        },
      ).timeout(
        const Duration(seconds: 30),
        onTimeout: () {
          throw TimeoutException('Request timeout. Please check your internet connection.');
        },
      );

      final data = json.decode(response.body);

      if (response.statusCode == 200 && data['success'] == true) {
        return {
          'success': true,
          'data': data['data'] ?? [],
        };
      } else {
        return _handleError(null, response);
      }
    } catch (e) {
      return _handleError(e, null);
    }
  }

  /// Create a new retirement request
  /// 
  /// [imprestRequestId] - ID of the imprest request to retire
  /// [retirementItems] - List of retirement items with actual amounts
  /// [retirementNotes] - Optional notes for the retirement
  /// 
  /// Returns Map with:
  /// - success: bool
  /// - message: Success or error message
  /// - data: Created retirement data if successful
  static Future<Map<String, dynamic>> createRetirement({
    required int imprestRequestId,
    required List<Map<String, dynamic>> retirementItems,
    String? retirementNotes,
  }) async {
    try {
      final token = await AuthService.getToken();
      final context = await AuthService.getSelectedContext();
      
      if (token == null) {
        return {
          'success': false,
          'message': 'Not authenticated. Please login again.',
          'error_code': 'AUTH_REQUIRED',
        };
      }

      final requestBody = {
        'imprest_request_id': imprestRequestId,
        'retirement_notes': retirementNotes,
        'retirement_items': retirementItems,
      };

      final response = await http.post(
        Uri.parse(ApiConfig.getUrl(ApiConfig.retirementCreate)),
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'X-Branch-Id': context['branch_id']?.toString() ?? '',
          'X-Location-Id': context['location_id']?.toString() ?? '',
        },
        body: json.encode(requestBody),
      ).timeout(
        const Duration(seconds: 30),
        onTimeout: () {
          throw TimeoutException('Request timeout. Please check your internet connection.');
        },
      );

      final data = json.decode(response.body);

      if (response.statusCode == 200 && data['success'] == true) {
        return {
          'success': true,
          'message': data['message'] ?? 'Retirement submitted successfully',
          'data': data['data'],
        };
      } else {
        return _handleError(null, response);
      }
    } catch (e) {
      return _handleError(e, null);
    }
  }

  // Helper for consistent error handling
  static Map<String, dynamic> _handleError(dynamic e, http.Response? response) {
    if (response != null) {
      try {
        final data = json.decode(response.body);
        String message = data['message'] ?? 'An unexpected error occurred.';
        
        if (response.statusCode == 401) {
          message = 'Session expired. Please log in again.';
        } else if (response.statusCode == 403) {
          message = 'You are not authorized to perform this action.';
        } else if (response.statusCode == 422 && data['errors'] != null) {
          final errors = data['errors'] as Map<String, dynamic>;
          message = errors.values.expand((e) => e as List).join('\n');
        }
        
        return {
          'success': false,
          'message': message,
          'statusCode': response.statusCode,
        };
      } catch (_) {
        return {
          'success': false,
          'message': 'An unexpected error occurred. Status: ${response.statusCode}',
          'statusCode': response.statusCode,
        };
      }
    } else if (e is TimeoutException) {
      return {
        'success': false,
        'message': 'Network request timed out. Please try again.',
        'statusCode': 408,
      };
    } else if (e is http.ClientException) {
      return {
        'success': false,
        'message': 'Network error: ${e.message}. Please check your internet connection.',
        'statusCode': 503,
      };
    } else if (e is FormatException) {
      return {
        'success': false,
        'message': 'Invalid response from server. Please try again later.',
        'statusCode': 500,
      };
    }
    
    return {
      'success': false,
      'message': 'An unknown error occurred: ${e.toString()}',
      'statusCode': 500,
    };
  }
}

