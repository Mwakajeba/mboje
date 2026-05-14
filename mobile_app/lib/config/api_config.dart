/// API Configuration
/// 
/// Update the base URL based on your environment:
/// - Development (Android): https://demo.smartsoft.co.tz/api
/// - Development (iOS Simulator): http://127.0.0.1:8000/api
/// - Development (Web): http://127.0.0.1:8000/api or http://localhost:8000/api
/// - Production: https://yourdomain.com/api

import 'package:flutter/foundation.dart' show kIsWeb, kReleaseMode;
import 'dart:io' show Platform;

class ApiConfig {
  // Development server domain for mobile app
  static const String devServer = 'demo.smartsoft.co.tz';
  
  // Development - Automatically detect platform
  // Android uses demo.smartsoft.co.tz for development
  // Web uses localhost (127.0.0.1)
  static String get baseUrl {
    // Check if running on web first (Platform doesn't work on web)
    if (kIsWeb) {
      // For web, use localhost
      return 'http://127.0.0.1:8000/api';
    }
    
    // Check if running on Android
    if (Platform.isAndroid) {
      // Use demo server for Android development
      return 'https://$devServer/api';
    } else if (Platform.isIOS) {
      // For iOS, use demo server for physical devices, localhost for simulator
      if (kReleaseMode) {
        return 'https://$devServer/api';
      } else {
        return 'http://127.0.0.1:8000/api';
      }
    } else {
      // For other platforms (desktop), use localhost
      return 'http://127.0.0.1:8000/api';
    }
  }
  
  // Production (uncomment and update when deploying)
  // static const String baseUrl = 'https://yourdomain.com/api';

  // API Endpoints
  static const String parentLogin = '/parent/login';
  static const String parentLogout = '/parent/logout';
  static const String parentMe = '/parent/me';
  static const String parentProfile = '/parent/profile';
  static const String parentChangePassword = '/parent/change-password';
  static const String parentForgotPassword = '/parent/forgot-password';
  static const String parentResetPassword = '/parent/reset-password';
  
  // Students
  static const String students = '/parent/students';
  static String student(int studentId) => '/parent/students/$studentId';
  static String studentSubjects(int studentId) => '/parent/students/$studentId/subjects';
  
  // Assignments
  static String studentAssignments(int studentId) => '/parent/students/$studentId/assignments';
  static String assignmentDetails(int studentId, int assignmentId) => '/parent/students/$studentId/assignments/$assignmentId';
  static String submitAssignment(int studentId, int assignmentId) => '/parent/students/$studentId/assignments/$assignmentId/submit';
  
  // Attendance
  static String studentAttendance(int studentId) => '/parent/students/$studentId/attendance';
  static String attendanceStats(int studentId) => '/parent/students/$studentId/attendance/stats';
  static String attendanceCalendar(int studentId) => '/parent/students/$studentId/attendance/calendar';
  
  // Exams
  static String studentExams(int studentId) => '/parent/students/$studentId/exams';
  static String examDetails(int studentId, int examTypeId, int academicYearId) => '/parent/students/$studentId/exams/$examTypeId/$academicYearId';
  static String studentResults(int studentId) => '/parent/students/$studentId/results';
  static String resultsByExamType(int studentId, int examTypeId) => '/parent/students/$studentId/results/$examTypeId';
  
  // Fees
  static String studentFees(int studentId) => '/parent/students/$studentId/fees';
  static String studentInvoices(int studentId) => '/parent/students/$studentId/fees/invoices';
  static String invoiceDetails(int studentId, int invoiceId) => '/parent/students/$studentId/fees/invoices/$invoiceId';
  static String studentPayments(int studentId) => '/parent/students/$studentId/fees/payments';
  static String studentFeeBalance(int studentId) => '/parent/students/$studentId/fees/balance';
  static String makePayment(int studentId) => '/parent/students/$studentId/fees/payment';
  static String prepaidAccountTransactions(int studentId) => '/parent/students/$studentId/fees/prepaid-transactions';
  
  // Notifications
  static const String notifications = '/parent/notifications';
  static const String unreadNotifications = '/parent/notifications/unread';
  static String markNotificationRead(int notificationId) => '/parent/notifications/$notificationId/read';
  static const String markAllNotificationsRead = '/parent/notifications/read-all';
  
  // Library Materials
  static String libraryMaterials(int studentId) => '/parent/students/$studentId/library';
  
  // Academic Info
  static String academicInfo(int studentId) => '/parent/students/$studentId/academic-info';
  static String timetable(int studentId) => '/parent/students/$studentId/timetable';
  static String events(int studentId) => '/parent/students/$studentId/events';
}

