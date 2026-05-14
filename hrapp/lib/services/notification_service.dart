import 'dart:convert';
import 'package:http/http.dart' as http;
import '../config/api_config.dart';
import 'auth_service.dart';

class NotificationService {
  /// Get notifications
  static Future<Map<String, dynamic>> getNotifications({int? page, int? perPage}) async {
    try {
      final token = await AuthService.getToken();
      
      if (token == null) {
        return {'success': false, 'message': 'Not authenticated'};
      }

      String url = ApiConfig.getUrl(ApiConfig.notifications);
      final params = <String>[];
      if (page != null) params.add('page=$page');
      if (perPage != null) params.add('per_page=$perPage');
      if (params.isNotEmpty) url += '?${params.join('&')}';

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
        };
      } else {
        return {
          'success': false,
          'message': data['message'] ?? 'Failed to load notifications',
        };
      }
    } catch (e) {
      return {'success': false, 'message': 'Network error: $e'};
    }
  }

  /// Get unread notifications count
  static Future<Map<String, dynamic>> getUnreadCount() async {
    try {
      final token = await AuthService.getToken();
      
      if (token == null) {
        return {'success': false, 'message': 'Not authenticated'};
      }

      final response = await http.get(
        Uri.parse(ApiConfig.getUrl(ApiConfig.unreadNotificationsCount)),
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
      ).timeout(const Duration(seconds: 30));

      final data = json.decode(response.body);
      
      if (response.statusCode == 200) {
        return {
          'success': true,
          'unread_count': data['data']?['unread_count'] ?? 0,
        };
      } else {
        return {'success': false, 'unread_count': 0};
      }
    } catch (e) {
      return {'success': false, 'unread_count': 0};
    }
  }

  /// Mark notification as read
  static Future<Map<String, dynamic>> markAsRead(int notificationId) async {
    try {
      final token = await AuthService.getToken();
      
      if (token == null) {
        return {'success': false, 'message': 'Not authenticated'};
      }

      final response = await http.put(
        Uri.parse(ApiConfig.getUrl(ApiConfig.markNotificationRead(notificationId))),
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
      ).timeout(const Duration(seconds: 30));

      final data = json.decode(response.body);
      
      if (response.statusCode == 200) {
        return {'success': true, 'message': data['message'] ?? 'Marked as read'};
      } else {
        return {
          'success': false,
          'message': data['message'] ?? 'Failed to mark as read',
        };
      }
    } catch (e) {
      return {'success': false, 'message': 'Network error: $e'};
    }
  }

  /// Mark all notifications as read
  static Future<Map<String, dynamic>> markAllAsRead() async {
    try {
      final token = await AuthService.getToken();
      
      if (token == null) {
        return {'success': false, 'message': 'Not authenticated'};
      }

      final response = await http.put(
        Uri.parse(ApiConfig.getUrl(ApiConfig.markAllNotificationsRead)),
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
      ).timeout(const Duration(seconds: 30));

      final data = json.decode(response.body);
      
      if (response.statusCode == 200) {
        return {'success': true, 'message': data['message'] ?? 'All marked as read'};
      } else {
        return {
          'success': false,
          'message': data['message'] ?? 'Failed to mark all as read',
        };
      }
    } catch (e) {
      return {'success': false, 'message': 'Network error: $e'};
    }
  }
}

