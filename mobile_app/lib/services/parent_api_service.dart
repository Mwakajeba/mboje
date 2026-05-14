import 'dart:convert';
import '../config/api_config.dart';
import 'api_service.dart';
import 'auth_service.dart';

/// Adapter service that provides the same interface as the old ParentApiService
/// but uses the new ApiService and AuthService underneath
class ParentApiService {
  static final ApiService _apiService = ApiService();
  static final AuthService _authService = AuthService();

  static Future<String?> getToken() async {
    return await _authService.getToken();
  }

  static Future<Map<String, dynamic>?> getGuardianInfo() async {
    try {
      final response = await _apiService.get(ApiConfig.parentMe);
      final data = response.data;
      if (data['success'] == true) {
        return data['data'];
      }
      return null;
    } catch (e) {
      print('Error fetching guardian info: $e');
      return null;
    }
  }

  static Future<void> logout() async {
    try {
      await _authService.logout();
    } catch (e) {
      print('Error logging out: $e');
    }
  }

  static Future<Map<String, dynamic>?> updateProfile(Map<String, dynamic> data) async {
    try {
      final response = await _apiService.put(ApiConfig.parentProfile, data: data);
      final result = response.data;
      if (result['success'] == true) {
        return result['data'];
      }
      return null;
    } catch (e) {
      print('Error updating profile: $e');
      return null;
    }
  }

  static Future<Map<String, dynamic>?> getStudentDetails(int studentId) async {
    try {
      final response = await _apiService.get(ApiConfig.student(studentId));
      final data = response.data;
      if (data['success'] == true) {
        return data['data'];
      }
      return null;
    } catch (e) {
      print('Error fetching student details: $e');
      return null;
    }
  }

  static Future<List<dynamic>?> getStudentSubjects(int studentId) async {
    try {
      final response = await _apiService.get(ApiConfig.studentSubjects(studentId));
      final data = response.data;
      if (data['success'] == true) {
        return List<dynamic>.from(data['data']);
      }
      return null;
    } catch (e) {
      print('Error fetching subjects: $e');
      return null;
    }
  }

  static Future<List<dynamic>?> getStudentExams(int studentId) async {
    try {
      final response = await _apiService.get(ApiConfig.studentExams(studentId));
      final data = response.data;
      if (data['success'] == true) {
        return List<dynamic>.from(data['data']);
      }
      return null;
    } catch (e) {
      print('Error fetching exams: $e');
      return null;
    }
  }

  static Future<Map<String, dynamic>?> getExamDetails(int studentId, int examTypeId, int academicYearId) async {
    try {
      final response = await _apiService.get(ApiConfig.examDetails(studentId, examTypeId, academicYearId));
      final data = response.data;
      if (data['success'] == true) {
        return data['data'];
      }
      return null;
    } catch (e) {
      print('Error fetching exam details: $e');
      return null;
    }
  }

  static Future<Map<String, dynamic>?> getPrepaidAccountTransactions(int studentId) async {
    try {
      final response = await _apiService.get(ApiConfig.prepaidAccountTransactions(studentId));
      final data = response.data;
      if (data['success'] == true) {
        return data['data'];
      }
      return null;
    } catch (e) {
      print('Error fetching prepaid account transactions: $e');
      return null;
    }
  }

  static Future<Map<String, dynamic>?> getStudentFees(int studentId) async {
    try {
      final response = await _apiService.get(ApiConfig.studentFees(studentId));
      final data = response.data;
      if (data['success'] == true) {
        return data['data'];
      }
      return null;
    } catch (e) {
      print('Error fetching fees: $e');
      return null;
    }
  }

  static Future<Map<String, dynamic>?> getInvoiceDetails(int studentId, int invoiceId) async {
    try {
      final response = await _apiService.get(ApiConfig.invoiceDetails(studentId, invoiceId));
      final data = response.data;
      if (data['success'] == true) {
        return data['data'];
      }
      return null;
    } catch (e) {
      print('Error fetching invoice details: $e');
      return null;
    }
  }

  static Future<Map<String, dynamic>?> getStudentAttendanceStats(
    int studentId, {
    String? startDate,
    String? endDate,
  }) async {
    try {
      final queryParams = <String, dynamic>{};
      if (startDate != null) queryParams['start_date'] = startDate;
      if (endDate != null) queryParams['end_date'] = endDate;
      
      final response = await _apiService.get(
        ApiConfig.attendanceStats(studentId),
        queryParameters: queryParams,
      );
      final data = response.data;
      if (data['success'] == true) {
        return data['data'];
      }
      return null;
    } catch (e) {
      print('Error fetching attendance stats: $e');
      return null;
    }
  }

  static Future<List<dynamic>?> getStudentAttendance(
    int studentId, {
    int limit = 30,
    String? startDate,
    String? endDate,
    String? status,
  }) async {
    try {
      final queryParams = <String, dynamic>{
        'limit': limit.toString(),
      };
      if (startDate != null) queryParams['start_date'] = startDate;
      if (endDate != null) queryParams['end_date'] = endDate;
      if (status != null) queryParams['status'] = status;
      
      final response = await _apiService.get(
        ApiConfig.studentAttendance(studentId),
        queryParameters: queryParams,
      );
      final data = response.data;
      if (data['success'] == true) {
        return List<dynamic>.from(data['data']);
      }
      return null;
    } catch (e) {
      print('Error fetching attendance: $e');
      return null;
    }
  }

  static Future<Map<String, dynamic>?> getAttendanceSummary(int studentId) async {
    try {
      final response = await _apiService.get(ApiConfig.attendanceStats(studentId));
      final data = response.data;
      if (data['success'] == true) {
        return data['data'];
      }
      return null;
    } catch (e) {
      print('Error fetching attendance summary: $e');
      return null;
    }
  }

  static Future<Map<String, dynamic>?> getAttendanceCalendar(
    int studentId, {
    required int year,
    required int month,
  }) async {
    try {
      final response = await _apiService.get(ApiConfig.attendanceCalendar(studentId));
      final data = response.data;
      if (data['success'] == true) {
        return data['data'];
      }
      return null;
    } catch (e) {
      print('Error fetching attendance calendar: $e');
      return null;
    }
  }

  static Future<Map<String, dynamic>?> getStudentAssignments(int studentId) async {
    try {
      final response = await _apiService.get(ApiConfig.studentAssignments(studentId));
      final data = response.data;
      if (data['success'] == true) {
        return data['data'];
      }
      return null;
    } catch (e) {
      print('Error fetching assignments: $e');
      return null;
    }
  }

  static Future<Map<String, dynamic>?> getTimetable(int studentId) async {
    try {
      final response = await _apiService.get(ApiConfig.timetable(studentId));
      final data = response.data;
      if (data['success'] == true) {
        final timetableData = data['data'];
        // Handle case where data might be a List (empty array) or Map
        if (timetableData is List) {
          // If it's an empty list, return empty map
          return <String, dynamic>{};
        } else if (timetableData is Map) {
          return Map<String, dynamic>.from(timetableData);
        }
        return null;
      }
      return null;
    } catch (e) {
      print('Error fetching timetable: $e');
      return null;
    }
  }

  static Future<List<dynamic>?> getLibraryMaterials(int studentId, {String? type, int? subjectId}) async {
    try {
      Map<String, dynamic>? queryParams;
      if (type != null || subjectId != null) {
        queryParams = {};
        if (type != null) queryParams['type'] = type;
        if (subjectId != null) queryParams['subject_id'] = subjectId;
      }
      
      final response = await _apiService.get(ApiConfig.libraryMaterials(studentId), queryParameters: queryParams);
      final data = response.data;
      if (data['success'] == true) {
        final materials = data['data'];
        if (materials is List) {
          return List<dynamic>.from(materials);
        }
        return [];
      }
      return [];
    } catch (e) {
      print('Error fetching library materials: $e');
      return [];
    }
  }

  static Future<Map<String, dynamic>?> getNotifications({int? studentId, int page = 1, int perPage = 20}) async {
    try {
      Map<String, dynamic>? queryParams = {
        'page': page,
        'per_page': perPage,
      };
      if (studentId != null) {
        queryParams['student_id'] = studentId;
      }
      
      final response = await _apiService.get(ApiConfig.notifications, queryParameters: queryParams);
      final data = response.data;
      if (data['success'] == true) {
        return data['data'];
      }
      return null;
    } catch (e) {
      print('Error fetching notifications: $e');
      return null;
    }
  }

  static Future<int?> getUnreadNotificationsCount({int? studentId}) async {
    try {
      Map<String, dynamic>? queryParams;
      if (studentId != null) {
        queryParams = {'student_id': studentId};
      }
      
      final response = await _apiService.get(ApiConfig.unreadNotifications, queryParameters: queryParams);
      final data = response.data;
      if (data['success'] == true) {
        return data['data']['unread_count'] as int?;
      }
      return 0;
    } catch (e) {
      print('Error fetching unread notifications count: $e');
      return 0;
    }
  }

  static Future<bool> markNotificationAsRead(int notificationId) async {
    try {
      final response = await _apiService.put(ApiConfig.markNotificationRead(notificationId));
      final data = response.data;
      return data['success'] == true;
    } catch (e) {
      print('Error marking notification as read: $e');
      return false;
    }
  }

  static Future<bool> markAllNotificationsAsRead({int? studentId}) async {
    try {
      Map<String, dynamic>? queryParams;
      if (studentId != null) {
        queryParams = {'student_id': studentId};
      }
      
      final response = await _apiService.put(ApiConfig.markAllNotificationsRead, queryParameters: queryParams);
      final data = response.data;
      return data['success'] == true;
    } catch (e) {
      print('Error marking all notifications as read: $e');
      return false;
    }
  }
}

