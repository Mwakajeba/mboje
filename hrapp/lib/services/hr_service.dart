import 'dart:convert';
import 'package:http/http.dart' as http;
import '../config/api_config.dart';
import 'auth_service.dart';

class HrService {
  // Helper method to format network errors
  static String _formatNetworkError(dynamic error) {
    final errorString = error.toString().toLowerCase();
    
    if (errorString.contains('failed host lookup') || 
        errorString.contains('connection refused') ||
        errorString.contains('socketexception') ||
        errorString.contains('network is unreachable')) {
      return 'Cannot connect to server. Please check your network connection.';
    } else if (errorString.contains('timeout')) {
      return 'Connection timeout. Please check your network connection.';
    } else {
      return 'Network error: ${error.toString()}';
    }
  }

  // Get dashboard data
  static Future<Map<String, dynamic>> getDashboard() async {
    try {
      final token = await AuthService.getToken();
      if (token == null) {
        return {'success': false, 'message': 'Not authenticated'};
      }

      final response = await http.get(
        Uri.parse(ApiConfig.getUrl('/dashboard')),
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'Authorization': 'Bearer $token',
        },
      ).timeout(
        const Duration(seconds: 30),
        onTimeout: () {
          throw Exception('Connection timeout');
        },
      );

      if (response.statusCode == 200) {
        return jsonDecode(response.body);
      } else {
        final data = jsonDecode(response.body);
        return {
          'success': false,
          'message': data['message'] ?? 'Failed to load dashboard',
        };
      }
    } catch (e) {
      return {
        'success': false,
        'message': _formatNetworkError(e),
      };
    }
  }

  // Get leave balances
  static Future<Map<String, dynamic>> getLeaveBalances() async {
    try {
      final token = await AuthService.getToken();
      if (token == null) {
        return {'success': false, 'message': 'Not authenticated'};
      }

      final response = await http.get(
        Uri.parse(ApiConfig.getUrl('/leave/balances')),
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'Authorization': 'Bearer $token',
        },
      ).timeout(const Duration(seconds: 30));

      if (response.statusCode == 200) {
        return jsonDecode(response.body);
      } else {
        final data = jsonDecode(response.body);
        return {
          'success': false,
          'message': data['message'] ?? 'Failed to load leave balances',
        };
      }
    } catch (e) {
      return {
        'success': false,
        'message': _formatNetworkError(e),
      };
    }
  }

  // Get leave types
  static Future<Map<String, dynamic>> getLeaveTypes() async {
    try {
      final token = await AuthService.getToken();
      if (token == null) {
        return {'success': false, 'message': 'Not authenticated'};
      }

      final response = await http.get(
        Uri.parse(ApiConfig.getUrl('/leave/types')),
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'Authorization': 'Bearer $token',
        },
      ).timeout(const Duration(seconds: 30));

      if (response.statusCode == 200) {
        return jsonDecode(response.body);
      } else {
        final data = jsonDecode(response.body);
        return {
          'success': false,
          'message': data['message'] ?? 'Failed to load leave types',
        };
      }
    } catch (e) {
      return {
        'success': false,
        'message': _formatNetworkError(e),
      };
    }
  }

  // Get leave requests
  static Future<Map<String, dynamic>> getLeaveRequests({String? status}) async {
    try {
      final token = await AuthService.getToken();
      if (token == null) {
        return {'success': false, 'message': 'Not authenticated'};
      }

      String url = ApiConfig.getUrl('/leave/requests');
      if (status != null && status.isNotEmpty) {
        url += '?status=$status';
      }

      final response = await http.get(
        Uri.parse(url),
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'Authorization': 'Bearer $token',
        },
      ).timeout(const Duration(seconds: 30));

      if (response.statusCode == 200) {
        return jsonDecode(response.body);
      } else {
        final data = jsonDecode(response.body);
        return {
          'success': false,
          'message': data['message'] ?? 'Failed to load leave requests',
        };
      }
    } catch (e) {
      return {
        'success': false,
        'message': _formatNetworkError(e),
      };
    }
  }

  // Apply for leave
  static Future<Map<String, dynamic>> applyLeave({
    required int leaveTypeId,
    required String startDate,
    required String endDate,
    required String reason,
    int? relieverId,
  }) async {
    try {
      final token = await AuthService.getToken();
      if (token == null) {
        return {'success': false, 'message': 'Not authenticated'};
      }

      final response = await http.post(
        Uri.parse(ApiConfig.getUrl('/leave/apply')),
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'Authorization': 'Bearer $token',
        },
        body: jsonEncode({
          'leave_type_id': leaveTypeId,
          'start_date': startDate,
          'end_date': endDate,
          'reason': reason,
          if (relieverId != null) 'reliever_id': relieverId,
        }),
      ).timeout(const Duration(seconds: 30));

      final data = jsonDecode(response.body);

      if (response.statusCode == 201 || response.statusCode == 200) {
        return data;
      } else {
        return {
          'success': false,
          'message': data['message'] ?? 'Failed to apply for leave',
        };
      }
    } catch (e) {
      return {
        'success': false,
        'message': _formatNetworkError(e),
      };
    }
  }

  // Get attendance
  static Future<Map<String, dynamic>> getAttendance({String? month}) async {
    try {
      final token = await AuthService.getToken();
      if (token == null) {
        return {'success': false, 'message': 'Not authenticated'};
      }

      String url = ApiConfig.getUrl('/attendance');
      if (month != null && month.isNotEmpty) {
        url += '?month=$month';
      }

      final response = await http.get(
        Uri.parse(url),
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'Authorization': 'Bearer $token',
        },
      ).timeout(const Duration(seconds: 30));

      if (response.statusCode == 200) {
        return jsonDecode(response.body);
      } else {
        final data = jsonDecode(response.body);
        return {
          'success': false,
          'message': data['message'] ?? 'Failed to load attendance',
        };
      }
    } catch (e) {
      return {
        'success': false,
        'message': _formatNetworkError(e),
      };
    }
  }

  // Get payslips
  static Future<Map<String, dynamic>> getPayslips({String? year}) async {
    try {
      final token = await AuthService.getToken();
      if (token == null) {
        return {'success': false, 'message': 'Not authenticated'};
      }

      String url = ApiConfig.getUrl('/payslips');
      if (year != null && year.isNotEmpty) {
        url += '?year=$year';
      }

      final response = await http.get(
        Uri.parse(url),
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'Authorization': 'Bearer $token',
        },
      ).timeout(const Duration(seconds: 30));

      if (response.statusCode == 200) {
        return jsonDecode(response.body);
      } else {
        final data = jsonDecode(response.body);
        return {
          'success': false,
          'message': data['message'] ?? 'Failed to load payslips',
        };
      }
    } catch (e) {
      return {
        'success': false,
        'message': _formatNetworkError(e),
      };
    }
  }

  // Get pending approvals (for managers)
  static Future<Map<String, dynamic>> getPendingApprovals() async {
    try {
      final token = await AuthService.getToken();
      if (token == null) {
        return {'success': false, 'message': 'Not authenticated'};
      }

      final response = await http.get(
        Uri.parse(ApiConfig.getUrl('/approvals/pending')),
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'Authorization': 'Bearer $token',
        },
      ).timeout(const Duration(seconds: 30));

      if (response.statusCode == 200) {
        return jsonDecode(response.body);
      } else {
        final data = jsonDecode(response.body);
        return {
          'success': false,
          'message': data['message'] ?? 'Failed to load pending approvals',
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

