import 'package:flutter/material.dart';
import 'dart:async';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:provider/provider.dart';
import 'login_screen.dart';
import '../../services/parent_api_service.dart';
import '../select_student.dart';
import '../home/home_screen.dart';
import '../../providers/language_provider.dart';

// Auth Wrapper to check if user is logged in and needs student selection
class AuthWrapper extends StatefulWidget {
  const AuthWrapper({super.key});

  @override
  State<AuthWrapper> createState() => _AuthWrapperState();
}

class _AuthWrapperState extends State<AuthWrapper> {
  bool _isLoading = true;
  bool _isLoggedIn = false;
  bool _needsStudentSelection = false;
  List<Map<String, dynamic>> _students = [];

  @override
  void initState() {
    super.initState();
    _checkAuthStatus();
  }

  Future<void> _checkAuthStatus() async {
    final prefs = await SharedPreferences.getInstance();
    final token = prefs.getString('parent_token');
    final selectedStudentId = prefs.getInt('selected_student_id');

    if (token != null) {
      // User is logged in, check if they need to select a student
      final guardianData = await ParentApiService.getGuardianInfo();
      if (guardianData != null) {
        final students =
            List<Map<String, dynamic>>.from(guardianData['students'] ?? []);
        if (students.length > 1 && selectedStudentId == null) {
          // Multiple students but none selected
          setState(() {
            _isLoggedIn = true;
            _needsStudentSelection = true;
            _students = students;
            _isLoading = false;
          });
        } else {
          // Single student or already selected
          if (students.isNotEmpty && selectedStudentId == null) {
            final studentId = students[0]['id'] is int
                ? students[0]['id'] as int
                : int.tryParse(students[0]['id'].toString()) ?? 0;
            await prefs.setInt('selected_student_id', studentId);
          }
          setState(() {
            _isLoggedIn = true;
            _needsStudentSelection = false;
            _isLoading = false;
          });
        }
      } else {
        setState(() {
          _isLoggedIn = false;
          _isLoading = false;
        });
      }
    } else {
      setState(() {
        _isLoggedIn = false;
        _isLoading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_isLoading) {
      return const Scaffold(
        body: Center(
          child: CircularProgressIndicator(),
        ),
      );
    }

    if (!_isLoggedIn) {
      return LoginScreen();
    }

    if (_needsStudentSelection) {
      // Ensure students list is properly formatted
      final formattedStudents = _students.map((s) {
        if (s is Map<String, dynamic>) {
          return s;
        }
        return <String, dynamic>{};
      }).toList();
      return StudentSelectionPage(students: formattedStudents);
    }

    return const HomePage();
  }
}

