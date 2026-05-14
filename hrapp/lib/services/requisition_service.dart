import 'dart:convert';
import 'package:http/http.dart' as http;
import '../config/api_config.dart';
import 'auth_service.dart';

/// Service for managing Store Requisition requests
/// Provides methods to create, view, and manage store requisitions
class RequisitionService {
  /// Get store requisitions list with optional status filter
  /// 
  /// [status] - Optional status filter: 'all', 'pending', 'approved', 'partially_issued', 'fully_issued', 'rejected'
  /// 
  /// Returns Map with:
  /// - success: bool
  /// - data: List of requisitions
  /// - stats: Statistics object
  /// - message: Error message if success is false
  static Future<Map<String, dynamic>> getStoreRequisitions({String? status}) async {
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

      String url = ApiConfig.getUrl(ApiConfig.storeRequisitions);
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
          'message': data['message'] ?? 'Failed to load requisitions',
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

  /// Get detailed information about a specific store requisition
  /// 
  /// [id] - The requisition ID
  /// 
  /// Returns Map with:
  /// - success: bool
  /// - data: Requisition details object
  /// - message: Error message if success is false
  static Future<Map<String, dynamic>> getRequisitionDetails(int id) async {
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
        Uri.parse(ApiConfig.getUrl(ApiConfig.storeRequisitionDetails(id))),
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
          'message': 'Requisition not found',
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
          'message': data['message'] ?? 'Failed to load requisition details',
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

  /// Get inventory items for requisition with optional search
  /// 
  /// [search] - Optional search query to filter items
  /// 
  /// Returns Map with:
  /// - success: bool
  /// - data: List of inventory items
  /// - message: Error message if success is false
  static Future<Map<String, dynamic>> getInventoryItems({String? search}) async {
    try {
      final token = await AuthService.getToken();
      final context = await AuthService.getSelectedContext();
      
      if (token == null) {
        return {
          'success': false,
          'message': 'Not authenticated',
          'error_code': 'AUTH_REQUIRED',
        };
      }

      String url = ApiConfig.getUrl(ApiConfig.inventoryItems);
      if (search != null && search.isNotEmpty) {
        url += '?search=${Uri.encodeComponent(search)}';
      }

      final response = await http.get(
        Uri.parse(url),
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
          'X-Branch-Id': context['branch_id']?.toString() ?? '',
          'X-Location-Id': context['location_id']?.toString() ?? '',
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
          'message': data['message'] ?? 'Failed to load inventory items',
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
    } on FormatException {
      return {
        'success': false,
        'message': 'Invalid response from server. Please try again.',
        'error_code': 'INVALID_RESPONSE',
      };
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

  /// Create a new store requisition
  /// 
  /// Required parameters:
  /// - [purpose] - Purpose of the requisition
  /// - [requiredDate] - Required date (YYYY-MM-DD format)
  /// - [items] - List of items with inventory_item_id, quantity_requested, and optional item_notes
  /// 
  /// Optional parameters:
  /// - [departmentId] - ID of the department (auto-assigned if not provided)
  /// - [notes] - Additional notes
  /// - [priority] - Priority level: 'low', 'normal', 'high', 'urgent' (default: 'normal')
  /// 
  /// Returns Map with:
  /// - success: bool
  /// - message: Success or error message
  /// - data: Created requisition data
  static Future<Map<String, dynamic>> createStoreRequisition({
    int? departmentId,
    required String purpose,
    String? notes,
    required String requiredDate,
    String priority = 'normal',
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
          'message': 'Please add at least one item to the requisition',
          'error_code': 'VALIDATION_ERROR',
        };
      }

      for (var item in items) {
        if (item['inventory_item_id'] == null) {
          return {
            'success': false,
            'message': 'All items must have an inventory item selected',
            'error_code': 'VALIDATION_ERROR',
          };
        }
        final qty = double.tryParse(item['quantity_requested'].toString()) ?? 0;
        if (qty <= 0) {
          return {
            'success': false,
            'message': 'All items must have a quantity greater than zero',
            'error_code': 'VALIDATION_ERROR',
          };
        }
      }

      // Validate priority
      if (!['low', 'normal', 'high', 'urgent'].contains(priority.toLowerCase())) {
        priority = 'normal';
      }

      final response = await http.post(
        Uri.parse(ApiConfig.getUrl(ApiConfig.storeRequisitions)),
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
          'notes': notes,
          'required_date': requiredDate,
          'priority': priority.toLowerCase(),
          'items': items.map((item) => {
            'inventory_item_id': item['inventory_item_id'],
            'quantity_requested': double.tryParse(item['quantity_requested'].toString()) ?? 0,
            'item_notes': item['item_notes'] ?? '',
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
          'message': data['message'] ?? 'Requisition created successfully',
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
          'message': data['message'] ?? 'Failed to create requisition',
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
