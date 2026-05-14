import 'dart:convert';
import 'package:http/http.dart' as http;
import '../config/api_config.dart';
import 'auth_service.dart';

/// Service for managing Imprest (Cash Advance) requests
/// Provides methods to create, view, and manage imprest requests
class ImprestService {
  /// Get imprest requests list with optional status filter
  /// 
  /// [status] - Optional status filter: 'all', 'pending', 'approved', 'disbursed', 'rejected'
  /// 
  /// Returns Map with:
  /// - success: bool
  /// - data: List of imprest requests
  /// - stats: Statistics object
  /// - message: Error message if success is false
  static Future<Map<String, dynamic>> getImprestRequests({String? status}) async {
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

      String url = ApiConfig.getUrl(ApiConfig.imprestList);
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
          throw Exception('Request timeout. Please check your internet connection.');
        },
      );

      final data = json.decode(response.body) as Map<String, dynamic>;
      
      if (response.statusCode == 200) {
        return {
          'success': true,
          'data': data['data'] ?? [],
          'stats': data['stats'] ?? {},
        };
      } else if (response.statusCode == 401) {
        return {
          'success': false,
          'message': 'Session expired. Please login again.',
          'error_code': 'SESSION_EXPIRED',
        };
      } else {
        return {
          'success': false,
          'message': data['message'] ?? 'Failed to load imprest requests',
          'error_code': 'API_ERROR',
        };
      }
    } on FormatException {
      return {
        'success': false,
        'message': 'Invalid response from server. Please try again.',
        'error_code': 'INVALID_RESPONSE',
      };
    } on Exception catch (e) {
      return {
        'success': false,
        'message': e.toString().replaceAll('Exception: ', ''),
        'error_code': 'NETWORK_ERROR',
      };
    } catch (e) {
      return {
        'success': false,
        'message': 'An unexpected error occurred. Please try again.',
        'error_code': 'UNKNOWN_ERROR',
      };
    }
  }

  /// Get detailed information about a specific imprest request
  /// 
  /// [id] - The imprest request ID
  /// 
  /// Returns Map with:
  /// - success: bool
  /// - data: Imprest request details object
  /// - message: Error message if success is false
  static Future<Map<String, dynamic>> getImprestDetails(int id) async {
    try {
      final token = await AuthService.getToken();
      
      if (token == null) {
        return {
          'success': false,
          'message': 'Not authenticated. Please login again.',
          'error_code': 'AUTH_REQUIRED',
        };
      }

      final response = await http.get(
        Uri.parse(ApiConfig.getUrl(ApiConfig.imprestDetails(id))),
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
      ).timeout(
        const Duration(seconds: 30),
        onTimeout: () {
          throw Exception('Request timeout. Please check your internet connection.');
        },
      ); 

      final data = json.decode(response.body) as Map<String, dynamic>;
      
      if (response.statusCode == 200) {
        return {
          'success': true,
          'data': data['data'],
        };
      } else if (response.statusCode == 404) {
        return {
          'success': false,
          'message': 'Imprest request not found',
          'error_code': 'NOT_FOUND',
        };
      } else if (response.statusCode == 401) {
        return {
          'success': false,
          'message': 'Session expired. Please login again.',
          'error_code': 'SESSION_EXPIRED',
        };
      } else {
        return {
          'success': false,
          'message': data['message'] ?? 'Failed to load imprest details',
          'error_code': 'API_ERROR',
        };
      }
    } on FormatException {
      return {
        'success': false,
        'message': 'Invalid response from server. Please try again.',
        'error_code': 'INVALID_RESPONSE',
      };
    } on Exception catch (e) {
      return {
        'success': false,
        'message': e.toString().replaceAll('Exception: ', ''),
        'error_code': 'NETWORK_ERROR',
      };
    } catch (e) {
      return {
        'success': false,
        'message': 'An unexpected error occurred. Please try again.',
        'error_code': 'UNKNOWN_ERROR',
      };
    }
  }

  /// Get list of expense accounts (Chart of Accounts) for imprest items
  /// 
  /// Returns Map with:
  /// - success: bool
  /// - data: List of expense accounts
  /// - message: Error message if success is false
  static Future<Map<String, dynamic>> getExpenseAccounts() async {
    try {
      final token = await AuthService.getToken();
      
      if (token == null) {
        return {
          'success': false,
          'message': 'Not authenticated',
          'error_code': 'AUTH_REQUIRED',
        };
      }

      final response = await http.get(
        Uri.parse(ApiConfig.getUrl(ApiConfig.expenseAccounts)),
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
      ).timeout(const Duration(seconds: 20));

      final data = json.decode(response.body) as Map<String, dynamic>;
      
      if (response.statusCode == 200) {
        return {
          'success': true,
          'data': data['data'] ?? [],
        };
      } else {
        return {
          'success': false,
          'message': data['message'] ?? 'Failed to load expense accounts',
          'error_code': 'API_ERROR',
        };
      }
    } on Exception catch (e) {
      return {
        'success': false,
        'message': 'Network error: ${e.toString()}',
        'error_code': 'NETWORK_ERROR',
      };
    } catch (e) {
      return {
        'success': false,
        'message': 'An unexpected error occurred',
        'error_code': 'UNKNOWN_ERROR',
      };
    }
  }

  /// Get list of departments
  /// 
  /// Returns Map with:
  /// - success: bool
  /// - data: List of departments
  /// - message: Error message if success is false
  static Future<Map<String, dynamic>> getDepartments() async {
    try {
      final token = await AuthService.getToken();
      
      if (token == null) {
        return {
          'success': false,
          'message': 'Not authenticated',
          'error_code': 'AUTH_REQUIRED',
        };
      }

      final response = await http.get(
        Uri.parse(ApiConfig.getUrl(ApiConfig.departments)),
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
      ).timeout(const Duration(seconds: 20));

      final data = json.decode(response.body) as Map<String, dynamic>;
      
      if (response.statusCode == 200) {
        return {
          'success': true,
          'data': data['data'] ?? [],
        };
      } else {
        return {
          'success': false,
          'message': data['message'] ?? 'Failed to load departments',
          'error_code': 'API_ERROR',
        };
      }
    } on Exception catch (e) {
      return {
        'success': false,
        'message': 'Network error: ${e.toString()}',
        'error_code': 'NETWORK_ERROR',
      };
    } catch (e) {
      return {
        'success': false,
        'message': 'An unexpected error occurred',
        'error_code': 'UNKNOWN_ERROR',
      };
    }
  }

  /// Create a new imprest request
  /// 
  /// Required parameters:
  /// - [departmentId] - ID of the department
  /// - [purpose] - Purpose of the imprest request
  /// - [dateRequired] - Required date (YYYY-MM-DD format)
  /// - [items] - List of expense items with chart_account_id, amount, and optional notes
  /// 
  /// Optional parameters:
  /// - [description] - Additional description
  /// 
  /// Returns Map with:
  /// - success: bool
  /// - message: Success or error message
  /// - data: Created imprest request data
  static Future<Map<String, dynamic>> createImprest({
    required int departmentId,
    required String purpose,
    String? description,
    required String dateRequired,
    required List<Map<String, dynamic>> items,
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

      // Validate items
      if (items.isEmpty) {
        return {
          'success': false,
          'message': 'Please add at least one expense item',
          'error_code': 'VALIDATION_ERROR',
        };
      }

      for (var item in items) {
        if (item['chart_account_id'] == null) {
          return {
            'success': false,
            'message': 'All items must have an expense account selected',
            'error_code': 'VALIDATION_ERROR',
          };
        }
        final amount = double.tryParse(item['amount'].toString()) ?? 0;
        if (amount <= 0) {
          return {
            'success': false,
            'message': 'All items must have an amount greater than zero',
            'error_code': 'VALIDATION_ERROR',
          };
        }
      }

      final response = await http.post(
        Uri.parse(ApiConfig.getUrl(ApiConfig.imprestCreate)),
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'X-Branch-Id': context['branch_id']?.toString() ?? '',
          'X-Location-Id': context['location_id']?.toString() ?? '',
        },
        body: json.encode({
          'department_id': departmentId,
          'purpose': purpose,
          'description': description,
          'date_required': dateRequired,
          'items': items.map((item) => {
            'chart_account_id': item['chart_account_id'],
            'amount': double.tryParse(item['amount'].toString()) ?? 0,
            'notes': item['notes'] ?? '',
          }).toList(),
        }),
      ).timeout(
        const Duration(seconds: 30),
        onTimeout: () {
          throw Exception('Request timeout. Please check your internet connection.');
        },
      );

      final data = json.decode(response.body) as Map<String, dynamic>;
      
      if (response.statusCode == 201 || response.statusCode == 200) {
        return {
          'success': true,
          'message': data['message'] ?? 'Imprest request created successfully',
          'data': data['data'],
        };
      } else if (response.statusCode == 422) {
        // Validation errors
        String errorMessage = data['message'] ?? 'Validation failed';
        if (data['errors'] != null) {
          final errors = data['errors'] as Map<String, dynamic>;
          final errorList = <String>[];
          errors.forEach((key, value) {
            if (value is List) {
              errorList.addAll(value.map((e) => e.toString()));
            } else {
              errorList.add(value.toString());
            }
          });
          errorMessage = errorList.join('\n');
        }
        return {
          'success': false,
          'message': errorMessage,
          'error_code': 'VALIDATION_ERROR',
        };
      } else if (response.statusCode == 401) {
        return {
          'success': false,
          'message': 'Session expired. Please login again.',
          'error_code': 'SESSION_EXPIRED',
        };
      } else {
        return {
          'success': false,
          'message': data['message'] ?? 'Failed to create imprest request',
          'error_code': 'API_ERROR',
        };
      }
    } on FormatException {
      return {
        'success': false,
        'message': 'Invalid response from server. Please try again.',
        'error_code': 'INVALID_RESPONSE',
      };
    } on Exception catch (e) {
      return {
        'success': false,
        'message': e.toString().replaceAll('Exception: ', ''),
        'error_code': 'NETWORK_ERROR',
      };
    } catch (e) {
      return {
        'success': false,
        'message': 'An unexpected error occurred. Please try again.',
        'error_code': 'UNKNOWN_ERROR',
      };
    }
  }
}
