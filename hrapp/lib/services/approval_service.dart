import 'dart:convert';
import 'package:http/http.dart' as http;
import '../config/api_config.dart';
import 'auth_service.dart';

class ApprovalService {
  /// Get pending approvals for manager
  static Future<Map<String, dynamic>> getPendingApprovals({String? type}) async {
    try {
      final token = await AuthService.getToken();
      
      if (token == null) {
        return {'success': false, 'message': 'Not authenticated'};
      }

      String url = ApiConfig.getUrl(ApiConfig.pendingApprovals);
      if (type != null && type != 'all') {
        url += '?type=$type';
      }

      final response = await http.get(
        Uri.parse(url),
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
      ).timeout(const Duration(seconds: 30));

      final data = json.decode(response.body);
      
      if (response.statusCode == 200) {
        return {
          'success': true,
          'data': data['data'] ?? [],
          'stats': data['stats'] ?? {},
        };
      } else {
        return {
          'success': false,
          'message': data['message'] ?? 'Failed to load approvals',
        };
      }
    } catch (e) {
      return {'success': false, 'message': 'Network error: $e'};
    }
  }

  /// Approve imprest request
  static Future<Map<String, dynamic>> approveImprest(int approvalId, {String? comments}) async {
    try {
      final token = await AuthService.getToken();
      
      if (token == null) {
        return {'success': false, 'message': 'Not authenticated'};
      }

      final response = await http.post(
        Uri.parse(ApiConfig.getUrl(ApiConfig.approveImprest(approvalId))),
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
        body: json.encode({'comments': comments ?? ''}),
      ).timeout(const Duration(seconds: 30));

      final data = json.decode(response.body);
      
      if (response.statusCode == 200) {
        return {
          'success': true,
          'message': data['message'] ?? 'Approved successfully',
        };
      } else {
        return {
          'success': false,
          'message': data['message'] ?? 'Failed to approve request',
        };
      }
    } catch (e) {
      return {'success': false, 'message': 'Network error: $e'};
    }
  }

  /// Reject imprest request
  static Future<Map<String, dynamic>> rejectImprest(int approvalId, String comments) async {
    try {
      final token = await AuthService.getToken();
      
      if (token == null) {
        return {'success': false, 'message': 'Not authenticated'};
      }

      final response = await http.post(
        Uri.parse(ApiConfig.getUrl(ApiConfig.rejectImprest(approvalId))),
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
        body: json.encode({'comments': comments}),
      ).timeout(const Duration(seconds: 30));

      final data = json.decode(response.body);
      
      if (response.statusCode == 200) {
        return {
          'success': true,
          'message': data['message'] ?? 'Rejected successfully',
        };
      } else {
        return {
          'success': false,
          'message': data['message'] ?? 'Failed to reject request',
        };
      }
    } catch (e) {
      return {'success': false, 'message': 'Network error: $e'};
    }
  }

  /// Approve store requisition
  static Future<Map<String, dynamic>> approveRequisition(int requisitionId, {String? comments}) async {
    try {
      final token = await AuthService.getToken();
      
      if (token == null) {
        return {'success': false, 'message': 'Not authenticated'};
      }

      final response = await http.post(
        Uri.parse(ApiConfig.getUrl(ApiConfig.approveRequisition(requisitionId))),
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
        body: json.encode({'comments': comments ?? ''}),
      ).timeout(const Duration(seconds: 30));

      final data = json.decode(response.body);
      
      if (response.statusCode == 200) {
        return {
          'success': true,
          'message': data['message'] ?? 'Approved successfully',
        };
      } else {
        return {
          'success': false,
          'message': data['message'] ?? 'Failed to approve requisition',
        };
      }
    } catch (e) {
      return {'success': false, 'message': 'Network error: $e'};
    }
  }

  /// Reject store requisition
  static Future<Map<String, dynamic>> rejectRequisition(int requisitionId, String comments) async {
    try {
      final token = await AuthService.getToken();
      
      if (token == null) {
        return {'success': false, 'message': 'Not authenticated'};
      }

      final response = await http.post(
        Uri.parse(ApiConfig.getUrl(ApiConfig.rejectRequisition(requisitionId))),
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
        body: json.encode({'comments': comments}),
      ).timeout(const Duration(seconds: 30));

      final data = json.decode(response.body);
      
      if (response.statusCode == 200) {
        return {
          'success': true,
          'message': data['message'] ?? 'Rejected successfully',
        };
      } else {
        return {
          'success': false,
          'message': data['message'] ?? 'Failed to reject requisition',
        };
      }
    } catch (e) {
      return {'success': false, 'message': 'Network error: $e'};
    }
  }
}

