import 'package:flutter/material.dart';
import 'dart:async';
import 'package:http/http.dart' as http;
import 'dart:convert';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:provider/provider.dart';
import 'login.dart';
import 'select_student.dart';
import 'results_fixed.dart'; // Fixed results screen
import 'fees.dart'; // Keep original if exists, or create new
import 'attendance_page_fixed.dart'; // Fixed attendance page
import 'assignment_fixed.dart'; // Fixed assignment page
import 'exam_detail_page_fixed.dart'; // Exam detail page
import 'language_provider.dart';

void main() {
  runApp(const SmartSchoolApp());
}

class SmartSchoolApp extends StatelessWidget {
  const SmartSchoolApp({super.key});

  @override
  Widget build(BuildContext context) {
    return ChangeNotifierProvider(
      create: (_) => LanguageProvider(),
      child: MaterialApp(
        title: 'SmartSchool Parent Portal',
        theme: ThemeData(
          useMaterial3: true,
          primarySwatch: Colors.blue,
          scaffoldBackgroundColor: Colors.white,
        ),
        home: const AuthWrapper(),
        debugShowCheckedModeBanner: false,
      ),
    );
  }
}

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
        final students = List<Map<String, dynamic>>.from(guardianData['students'] ?? []);
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
      return const LoginPage();
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

// API Service Class
class ParentApiService {
  static const String baseUrl = 'https://demo.smartsoft.co.tz/api/parent';

  static Future<String?> getToken() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString('parent_token');
  }

  static Future<Map<String, dynamic>?> getGuardianInfo() async {
    try {
      final token = await getToken();
      if (token == null) return null;

      final response = await http.get(
        Uri.parse('$baseUrl/me'),
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
      );

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['success'] == true) {
          return data['data'];
        }
      }
      return null;
    } catch (e) {
      print('Error fetching guardian info: $e');
      return null;
    }
  }

  static Future<void> logout() async {
    try {
      final token = await getToken();
      if (token == null) return;

      await http.post(
        Uri.parse('$baseUrl/logout'),
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
      );

      final prefs = await SharedPreferences.getInstance();
      await prefs.remove('parent_token');
      await prefs.remove('parent_name');
      await prefs.remove('parent_phone');
      await prefs.remove('selected_student_id');
    } catch (e) {
      print('Error logging out: $e');
    }
  }

  static Future<Map<String, dynamic>?> updateProfile(Map<String, dynamic> data) async {
    try {
      final token = await getToken();
      if (token == null) return null;

      final response = await http.put(
        Uri.parse('$baseUrl/profile'),
        headers: {
          'Authorization': 'Bearer $token',
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: jsonEncode(data),
      );

      if (response.statusCode == 200) {
        final result = jsonDecode(response.body);
        if (result['success'] == true) {
          return result['data'];
        }
      }
      return null;
    } catch (e) {
      print('Error updating profile: $e');
      return null;
    }
  }

  static Future<Map<String, dynamic>?> getStudentDetails(int studentId) async {
    try {
      final token = await getToken();
      if (token == null) return null;

      final response = await http.get(
        Uri.parse('$baseUrl/students/$studentId'),
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
      );

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['success'] == true) {
          return data['data'];
        }
      }
      return null;
    } catch (e) {
      print('Error fetching student details: $e');
      return null;
    }
  }

  static Future<List<dynamic>?> getStudentSubjects(int studentId) async {
    try {
      final token = await getToken();
      if (token == null) return null;

      final response = await http.get(
        Uri.parse('$baseUrl/students/$studentId/subjects'),
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
      );

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['success'] == true) {
          return List<dynamic>.from(data['data']);
        }
      }
      return null;
    } catch (e) {
      print('Error fetching subjects: $e');
      return null;
    }
  }

  static Future<List<dynamic>?> getStudentExams(int studentId) async {
    try {
      final token = await getToken();
      if (token == null) return null;

      final response = await http.get(
        Uri.parse('$baseUrl/students/$studentId/exams'),
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
      );

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['success'] == true) {
          return List<dynamic>.from(data['data']);
        }
      }
      return null;
    } catch (e) {
      print('Error fetching exams: $e');
      return null;
    }
  }

  static Future<Map<String, dynamic>?> getExamDetails(int studentId, int examTypeId, int academicYearId) async {
    try {
      final token = await getToken();
      if (token == null) return null;

      final response = await http.get(
        Uri.parse('$baseUrl/students/$studentId/exams/$examTypeId/$academicYearId'),
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
      );

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['success'] == true) {
          return data['data'];
        }
      }
      return null;
    } catch (e) {
      print('Error fetching exam details: $e');
      return null;
    }
  }

  static Future<Map<String, dynamic>?> getStudentFees(int studentId) async {
    try {
      final token = await getToken();
      if (token == null) return null;

      final response = await http.get(
        Uri.parse('$baseUrl/students/$studentId/fees'),
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
      );

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['success'] == true) {
          return data['data'];
        }
      }
      return null;
    } catch (e) {
      print('Error fetching fees: $e');
      return null;
    }
  }

  static Future<Map<String, dynamic>?> getStudentAssignments(int studentId) async {
    try {
      final token = await getToken();
      if (token == null) return null;

      final response = await http.get(
        Uri.parse('$baseUrl/students/$studentId/assignments'),
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
      );

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['success'] == true) {
          return data['data'];
        }
      }
      return null;
    } catch (e) {
      print('Error fetching assignments: $e');
      return null;
    }
  }

  static Future<Map<String, dynamic>?> getStudentAttendanceStats(int studentId, {String? startDate, String? endDate}) async {
    try {
      final token = await getToken();
      if (token == null) return null;

      String url = '$baseUrl/students/$studentId/attendance/stats';
      final uri = Uri.parse(url);
      final queryParams = <String, String>{};
      
      if (startDate != null) queryParams['start_date'] = startDate;
      if (endDate != null) queryParams['end_date'] = endDate;
      
      final finalUri = queryParams.isEmpty ? uri : uri.replace(queryParameters: queryParams);

      final response = await http.get(
        finalUri,
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
      );

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['success'] == true) {
          return data['data'];
        }
      }
      return null;
    } catch (e) {
      print('Error fetching attendance stats: $e');
      return null;
    }
  }

  static Future<List<dynamic>?> getStudentAttendance(int studentId, {String? startDate, String? endDate, int? limit}) async {
    try {
      final token = await getToken();
      if (token == null) return null;

      String url = '$baseUrl/students/$studentId/attendance';
      final uri = Uri.parse(url);
      final queryParams = <String, String>{};
      
      if (startDate != null) queryParams['start_date'] = startDate;
      if (endDate != null) queryParams['end_date'] = endDate;
      if (limit != null) queryParams['limit'] = limit.toString();
      
      final finalUri = queryParams.isEmpty ? uri : uri.replace(queryParameters: queryParams);

      final response = await http.get(
        finalUri,
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
      );

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['success'] == true) {
          return List<dynamic>.from(data['data']);
        }
      }
      return null;
    } catch (e) {
      print('Error fetching attendance: $e');
      return null;
    }
  }
}

class LoginPage extends StatefulWidget {
  const LoginPage({super.key});

  @override
  State<LoginPage> createState() => _LoginPageState();
}

class _LoginPageState extends State<LoginPage> {
  final TextEditingController _phoneController = TextEditingController();
  final TextEditingController _passwordController = TextEditingController();
  bool _rememberMe = false;
  bool _isLoading = false;

  void _login() async {
    if (_phoneController.text.isEmpty || _passwordController.text.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Please fill in all fields')),
      );
      return;
    }

    setState(() {
      _isLoading = true;
    });

    try {
      final response = await http.post(
        Uri.parse('$baseUrl/login'),
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: jsonEncode({
          'phone': _phoneController.text.trim(),
          'password': _passwordController.text,
        }),
      );

      print('Status Code: ${response.statusCode}');
      print('Response Body: ${response.body}');

      // Check if response is JSON or HTML
      if (response.headers['content-type']?.contains('application/json') == false) {
        throw Exception('Server returned HTML instead of JSON. Check the API endpoint.');
      }

      // Parse the JSON response
      final responseData = jsonDecode(response.body);

      if (response.statusCode == 200 && responseData['success'] == true) {
        // Save token for future requests
        final token = responseData['data']['token'];
        final prefs = await SharedPreferences.getInstance();
        await prefs.setString('parent_token', token);

        // Save user data if needed
        if (responseData['data']['user'] != null) {
          await prefs.setString('parent_name', responseData['data']['user']['name'] ?? '');
          await prefs.setString('parent_phone', responseData['data']['user']['phone'] ?? '');
        }

        // Check if parent has multiple students
        final students = responseData['data']['user']?['students'] ?? [];
        if (students.length > 1) {
          // Navigate to student selection page
          if (mounted) {
            Navigator.of(context).pushReplacement(
              MaterialPageRoute(builder: (context) => StudentSelectionPage(students: List<Map<String, dynamic>>.from(students))),
            );
          }
        } else {
          // Single student or no students - go directly to home
          if (students.isNotEmpty) {
            final studentId = students[0]['id'] is int 
                ? students[0]['id'] as int
                : int.tryParse(students[0]['id'].toString()) ?? 0;
            await prefs.setInt('selected_student_id', studentId);
          }
          if (mounted) {
            Navigator.of(context).pushReplacement(
              MaterialPageRoute(builder: (context) => const HomePage()),
            );
          }
        }
      } else {
        // Show error message from API
        final errorMessage = responseData['message'] ?? 'Login failed';
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(errorMessage),
              backgroundColor: Colors.red,
            ),
          );
        }
      }
    } catch (e) {
      print('Error: $e');
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Error: ${e.toString()}'),
            backgroundColor: Colors.red,
          ),
        );
      }
    } finally {
      if (mounted) {
        setState(() {
          _isLoading = false;
        });
      }
    }
  }

  Widget _buildInputField({
    required String label,
    required IconData icon,
    required TextEditingController controller,
    bool isPassword = false,
    String? hintText,
  }) {
    return Container(
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.05),
            blurRadius: 10,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: TextField(
        controller: controller,
        obscureText: isPassword,
        style: const TextStyle(
          color: Colors.black87,
          fontSize: 16,
          fontWeight: FontWeight.w500,
        ),
        decoration: InputDecoration(
          labelText: label,
          hintText: hintText,
          labelStyle: TextStyle(
            color: Colors.grey.shade600,
            fontSize: 14,
          ),
          hintStyle: TextStyle(
            color: Colors.grey.shade400,
            fontSize: 14,
          ),
          prefixIcon: Container(
            margin: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: Colors.blue.shade50,
              borderRadius: BorderRadius.circular(12),
            ),
            child: Icon(
              icon,
              color: Colors.blue.shade700,
              size: 20,
            ),
          ),
          filled: true,
          fillColor: Colors.white,
          contentPadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 18),
          border: OutlineInputBorder(
            borderRadius: BorderRadius.circular(16),
            borderSide: BorderSide(color: Colors.grey.shade200),
          ),
          enabledBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(16),
            borderSide: BorderSide(color: Colors.grey.shade200),
          ),
          focusedBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(16),
            borderSide: BorderSide(color: Colors.blue.shade400, width: 2),
          ),
          errorBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(16),
            borderSide: const BorderSide(color: Colors.red, width: 1),
          ),
          focusedErrorBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(16),
            borderSide: const BorderSide(color: Colors.red, width: 2),
          ),
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Container(
        decoration: BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
            colors: [
              Colors.blue.shade700,
              Colors.blue.shade500,
              Colors.blue.shade300,
            ],
          ),
        ),
        child: SafeArea(
          child: SingleChildScrollView(
            child: Padding(
              padding: const EdgeInsets.symmetric(horizontal: 24),
              child: Column(
                children: [
                  const SizedBox(height: 40),
                  
                  // Logo Section
                  Container(
                    width: 120,
                    height: 120,
                    decoration: BoxDecoration(
                      shape: BoxShape.circle,
                      color: Colors.white,
                      boxShadow: [
                        BoxShadow(
                          color: Colors.black.withOpacity(0.1),
                          blurRadius: 20,
                          offset: const Offset(0, 10),
                        ),
                      ],
                    ),
                    child: Icon(
                      Icons.school,
                      size: 60,
                      color: Colors.blue.shade700,
                    ),
                  ),
                  
                  const SizedBox(height: 30),
                  
                  // Welcome Text
                  const Text(
                    'Karibu',
                    style: TextStyle(
                      fontSize: 36,
                      fontWeight: FontWeight.bold,
                      color: Colors.white,
                      letterSpacing: 1.2,
                    ),
                  ),
                  
                  const SizedBox(height: 8),
                  
                  Text(
                    'Parent Portal',
                    style: TextStyle(
                      fontSize: 18,
                      color: Colors.white.withOpacity(0.9),
                      fontWeight: FontWeight.w500,
                    ),
                  ),
                  
                  const SizedBox(height: 8),
                  
                  Text(
                    'Ingia kwa kutumia akaunti yako',
                    style: TextStyle(
                      fontSize: 14,
                      color: Colors.white.withOpacity(0.8),
                    ),
                  ),
                  
                  const SizedBox(height: 50),
                  
                  // Login Card
                  Container(
                    width: double.infinity,
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(24),
                      boxShadow: [
                        BoxShadow(
                          color: Colors.black.withOpacity(0.1),
                          blurRadius: 30,
                          offset: const Offset(0, 15),
                        ),
                      ],
                    ),
                    child: Padding(
                      padding: const EdgeInsets.all(28),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.stretch,
                        children: [
                          // Phone Number Field
                          _buildInputField(
                            label: 'Nambari ya Simu',
                            icon: Icons.phone_android,
                            controller: _phoneController,
                            hintText: '255712345678',
                          ),
                          
                          const SizedBox(height: 24),
                          
                          // Password Field
                          _buildInputField(
                            label: 'Nenosiri',
                            icon: Icons.lock_outline,
                            controller: _passwordController,
                            isPassword: true,
                            hintText: 'Ingiza nenosiri lako',
                          ),
                          
                          const SizedBox(height: 20),
                          
                          // Remember Me & Forgot Password Row
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              Row(
                                children: [
                                  Checkbox(
                                    value: _rememberMe,
                                    onChanged: (value) {
                                      setState(() {
                                        _rememberMe = value ?? false;
                                      });
                                    },
                                    activeColor: Colors.blue.shade700,
                                    shape: RoundedRectangleBorder(
                                      borderRadius: BorderRadius.circular(4),
                                    ),
                                  ),
                                  Text(
                                    'Nikumbuke',
                                    style: TextStyle(
                                      color: Colors.grey.shade700,
                                      fontSize: 14,
                                    ),
                                  ),
                                ],
                              ),
                              TextButton(
                                onPressed: () {
                                  ScaffoldMessenger.of(context).showSnackBar(
                                    const SnackBar(
                                      content: Text('Fungua kwa mwalimu au ofisi'),
                                    ),
                                  );
                                },
                                child: Text(
                                  'Umesahau?',
                                  style: TextStyle(
                                    color: Colors.blue.shade700,
                                    fontSize: 14,
                                    fontWeight: FontWeight.w600,
                                  ),
                                ),
                              ),
                            ],
                          ),
                          
                          const SizedBox(height: 32),
                          
                          // Login Button
                          Container(
                            height: 56,
                            decoration: BoxDecoration(
                              borderRadius: BorderRadius.circular(16),
                              gradient: LinearGradient(
                                colors: [
                                  Colors.blue.shade700,
                                  Colors.blue.shade600,
                                ],
                              ),
                              boxShadow: [
                                BoxShadow(
                                  color: Colors.blue.withOpacity(0.3),
                                  blurRadius: 15,
                                  offset: const Offset(0, 8),
                                ),
                              ],
                            ),
                            child: ElevatedButton(
                              onPressed: _isLoading ? null : _login,
                              style: ElevatedButton.styleFrom(
                                backgroundColor: Colors.transparent,
                                shadowColor: Colors.transparent,
                                shape: RoundedRectangleBorder(
                                  borderRadius: BorderRadius.circular(16),
                                ),
                                padding: const EdgeInsets.symmetric(vertical: 16),
                              ),
                              child: _isLoading
                                  ? const SizedBox(
                                      width: 24,
                                      height: 24,
                                      child: CircularProgressIndicator(
                                        color: Colors.white,
                                        strokeWidth: 2.5,
                                      ),
                                    )
                                  : const Text(
                                      'Ingia',
                                      style: TextStyle(
                                        fontSize: 18,
                                        fontWeight: FontWeight.bold,
                                        color: Colors.white,
                                        letterSpacing: 1,
                                      ),
                                    ),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                  
                  const SizedBox(height: 30),
                  
                  // Footer Text
                  Text(
                    '© SmartSchool Parent Portal',
                    style: TextStyle(
                      color: Colors.white.withOpacity(0.7),
                      fontSize: 12,
                    ),
                  ),
                  
                  const SizedBox(height: 20),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }

  @override
  void dispose() {
    _phoneController.dispose();
    _passwordController.dispose();
    super.dispose();
  }
}

// Student Selection Page
class StudentSelectionPage extends StatefulWidget {
  final List<Map<String, dynamic>> students;
  
  const StudentSelectionPage({super.key, required this.students});

  @override
  State<StudentSelectionPage> createState() => _StudentSelectionPageState();
}

class _StudentSelectionPageState extends State<StudentSelectionPage> {
  int? _selectedStudentId;
  bool _isLoading = false;

  Future<void> _selectStudent() async {
    if (_selectedStudentId == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Please select a student to continue'),
          backgroundColor: Colors.red,
        ),
      );
      return;
    }

    setState(() {
      _isLoading = true;
    });

    // Save selected student ID
    final prefs = await SharedPreferences.getInstance();
    await prefs.setInt('selected_student_id', _selectedStudentId!);

    if (mounted) {
      Navigator.of(context).pushReplacement(
        MaterialPageRoute(builder: (context) => const HomePage()),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Select Student'),
        backgroundColor: Colors.blue,
        foregroundColor: Colors.white,
        automaticallyImplyLeading: false,
      ),
      body: Container(
        decoration: const BoxDecoration(
          gradient: LinearGradient(
            colors: [Color(0xFF2E9AFE), Color(0xFF7FC7FF)],
            begin: Alignment.topCenter,
            end: Alignment.bottomCenter,
          ),
        ),
        child: SafeArea(
          child: Padding(
            padding: const EdgeInsets.all(20.0),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                const SizedBox(height: 20),
                const Text(
                  'Select a Student',
                  style: TextStyle(
                    fontSize: 24,
                    fontWeight: FontWeight.bold,
                    color: Colors.white,
                  ),
                  textAlign: TextAlign.center,
                ),
                const SizedBox(height: 10),
                const Text(
                  'You have multiple students. Please select which student you want to view.',
                  style: TextStyle(
                    fontSize: 16,
                    color: Colors.white70,
                  ),
                  textAlign: TextAlign.center,
                ),
                const SizedBox(height: 30),
                Expanded(
                  child: widget.students.isEmpty
                      ? const Center(
                          child: Text(
                            'No students found',
                            style: TextStyle(color: Colors.white),
                          ),
                        )
                      : ListView.builder(
                          itemCount: widget.students.length,
                          itemBuilder: (context, index) {
                            try {
                              final student = widget.students[index];
                              if (student is! Map<String, dynamic>) {
                                return const SizedBox.shrink();
                              }
                              
                              final studentId = student['id'] is int 
                                  ? student['id'] as int
                                  : (student['id'] != null 
                                      ? int.tryParse(student['id'].toString()) ?? 0
                                      : 0);
                              final isSelected = _selectedStudentId == studentId;
                              
                              final studentName = student['name']?.toString() ?? 'Student';
                              
                              String className = 'N/A';
                              if (student['class'] != null) {
                                if (student['class'] is Map) {
                                  className = student['class']?['name']?.toString() ?? 'N/A';
                                } else if (student['class'] is String) {
                                  className = student['class'] as String;
                                }
                              }
                              
                              String streamName = '';
                              if (student['stream'] != null) {
                                if (student['stream'] is Map) {
                                  streamName = student['stream']?['name']?.toString() ?? '';
                                } else if (student['stream'] is String) {
                                  streamName = student['stream'] as String;
                                }
                              }
                              
                              final studentGrade = streamName.isNotEmpty 
                                  ? '$className - $streamName'
                                  : className;

                              return Card(
                                margin: const EdgeInsets.only(bottom: 15),
                                elevation: isSelected ? 8 : 2,
                                shape: RoundedRectangleBorder(
                                  borderRadius: BorderRadius.circular(12),
                                  side: BorderSide(
                                    color: isSelected ? Colors.blue : Colors.transparent,
                                    width: isSelected ? 3 : 0,
                                  ),
                                ),
                                child: InkWell(
                                  onTap: () {
                                    setState(() {
                                      _selectedStudentId = studentId;
                                    });
                                  },
                                  borderRadius: BorderRadius.circular(12),
                                  child: Padding(
                                    padding: const EdgeInsets.all(16.0),
                                    child: Row(
                                      children: [
                                        Container(
                                          width: 60,
                                          height: 60,
                                          decoration: BoxDecoration(
                                            color: isSelected ? Colors.blue.shade100 : Colors.grey.shade200,
                                            shape: BoxShape.circle,
                                          ),
                                          child: Icon(
                                            Icons.person,
                                            size: 35,
                                            color: isSelected ? Colors.blue : Colors.grey,
                                          ),
                                        ),
                                        const SizedBox(width: 16),
                                        Expanded(
                                          child: Column(
                                            crossAxisAlignment: CrossAxisAlignment.start,
                                            children: [
                                              Text(
                                                studentName,
                                                style: TextStyle(
                                                  fontSize: 18,
                                                  fontWeight: FontWeight.bold,
                                                  color: isSelected ? Colors.blue : Colors.black87,
                                                ),
                                              ),
                                              const SizedBox(height: 4),
                                              Text(
                                                studentGrade,
                                                style: TextStyle(
                                                  fontSize: 14,
                                                  color: Colors.black54,
                                                ),
                                              ),
                                            ],
                                          ),
                                        ),
                                        if (isSelected)
                                          const Icon(
                                            Icons.check_circle,
                                            color: Colors.blue,
                                            size: 30,
                                          ),
                                      ],
                                    ),
                                  ),
                                ),
                              );
                            } catch (e) {
                              return Card(
                                margin: const EdgeInsets.only(bottom: 15),
                                child: Padding(
                                  padding: const EdgeInsets.all(16.0),
                                  child: Text(
                                    'Error loading student: $e',
                                    style: const TextStyle(color: Colors.red),
                                  ),
                                ),
                              );
                            }
                          },
                        ),
                ),
                const SizedBox(height: 20),
                SizedBox(
                  width: double.infinity,
                  height: 50,
                  child: ElevatedButton(
                    onPressed: _isLoading ? null : _selectStudent,
                    style: ElevatedButton.styleFrom(
                      backgroundColor: Colors.white,
                      foregroundColor: Colors.blue,
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(12),
                      ),
                      elevation: 4,
                    ),
                    child: _isLoading
                        ? const SizedBox(
                            height: 20,
                            width: 20,
                            child: CircularProgressIndicator(strokeWidth: 2),
                          )
                        : const Text(
                            'Continue',
                            style: TextStyle(
                              fontSize: 18,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class HomePage extends StatefulWidget {
  const HomePage({super.key});

  @override
  State<HomePage> createState() => _HomePageState();
}

class _HomePageState extends State<HomePage> {
  int _selectedIndex = 0;
  final PageController _carouselController = PageController();
  Timer? _timer;
  String parentName = 'Loading...';
  List<Map<String, dynamic>> students = [];
  List<dynamic> exams = [];
  bool isLoading = true;
  int? selectedStudentId;

  @override
  void initState() {
    super.initState();
    _loadSelectedStudentId();
    _loadGuardianData();
    _timer = Timer.periodic(const Duration(seconds: 3), (Timer timer) {
      if (_carouselController.hasClients) {
        int nextPage = (_carouselController.page!.toInt() + 1) % 3;
        _carouselController.animateToPage(
          nextPage,
          duration: const Duration(milliseconds: 300),
          curve: Curves.easeIn,
        );
      }
    });
  }

  Future<void> _loadSelectedStudentId() async {
    final prefs = await SharedPreferences.getInstance();
    setState(() {
      selectedStudentId = prefs.getInt('selected_student_id');
    });
  }

  Future<void> _loadGuardianData() async {
    final data = await ParentApiService.getGuardianInfo();
    if (mounted && data != null) {
      setState(() {
        parentName = data['name'] ?? 'Parent';
        students = List<Map<String, dynamic>>.from(data['students'] ?? []);
        isLoading = false;
      });
      // Load exams for the selected student
      if (selectedStudentId != null) {
        _loadExams(selectedStudentId!);
      } else if (students.isNotEmpty) {
        final firstStudentId = _getStudentId(students[0]['id']);
        if (firstStudentId != null) {
          _loadExams(firstStudentId);
        }
      }
    } else {
      final prefs = await SharedPreferences.getInstance();
      if (mounted) {
        setState(() {
          parentName = prefs.getString('parent_name') ?? 'Parent';
          isLoading = false;
        });
      }
    }
  }

  Future<void> _loadExams(int studentId) async {
    try {
      final data = await ParentApiService.getStudentExams(studentId);
      if (mounted) {
        setState(() {
          exams = data ?? [];
        });
      }
    } catch (e) {
      // Silently fail - exams are optional for home page
    }
  }

  @override
  void dispose() {
    _timer?.cancel();
    _carouselController.dispose();
    super.dispose();
  }

  void _onBottomNavTap(int idx) {
    setState(() {
      _selectedIndex = idx;
    });
  }

  int? _getStudentId(dynamic id) {
    if (id == null) return null;
    if (id is int) return id;
    return int.tryParse(id.toString());
  }

  Future<void> _changeStudent() async {
    if (mounted) {
      Navigator.of(context).pushReplacement(
        MaterialPageRoute(
          builder: (context) => StudentSelectionPage(students: students),
        ),
      );
    }
  }

  Future<void> _logout() async {
    await ParentApiService.logout();
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove('selected_student_id');
    if (mounted) {
      Navigator.of(context).pushReplacement(
        MaterialPageRoute(builder: (context) => const LoginPage()),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    Map<String, dynamic>? selectedStudent;
    if (selectedStudentId != null && students.isNotEmpty) {
      selectedStudent = students.firstWhere(
        (s) => _getStudentId(s['id']) == selectedStudentId,
        orElse: () => students[0],
      );
    } else if (students.isNotEmpty) {
      selectedStudent = students[0];
    }
    
    final student = selectedStudent;
    final String studentName = student?['name'] ?? 'Student';
    final String studentGrade = student != null && student['class'] != null && student['stream'] != null
        ? '${student['class']} - ${student['stream']}'
        : 'Grade 6 — A';
    const double feesPaidPercent = 0.65;
    
    final int? currentStudentId = selectedStudentId ?? _getStudentId(student?['id']);

    return Scaffold(
      backgroundColor: Colors.grey.shade50,
      appBar: AppBar(
        flexibleSpace: Container(
          decoration: BoxDecoration(
            gradient: LinearGradient(
              colors: [Colors.blue.shade700, Colors.blue.shade500],
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
            ),
          ),
        ),
        elevation: 0,
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Karibu,',
              style: TextStyle(
                color: Colors.white.withOpacity(0.9),
                fontSize: 14,
                fontWeight: FontWeight.normal,
              ),
            ),
            Text(
              parentName,
              style: const TextStyle(
                color: Colors.white,
                fontSize: 20,
                fontWeight: FontWeight.bold,
              ),
            ),
          ],
        ),
        actions: [
          IconButton(
            onPressed: () {
              showDialog(
                context: context,
                builder: (context) => AlertDialog(
                  title: const Text('Arifa'),
                  content: const Text(
                      'Mkutano: Mzazi–Mwalimu tarehe 12 Des 2025 — 10:00 AM\n\nHabari za Leo:\n• Darasa: Form 6\n• Kazi za Nyumbani: 2 Zimepewa\n• Masomo Yaliyohudhuriwa: 6/7\n• ⭐ Tabia: Nzuri'),
                  actions: [
                    TextButton(
                      onPressed: () => Navigator.of(context).pop(),
                      child: const Text('Sawa'),
                    ),
                  ],
                ),
              );
            },
            icon: Stack(
              children: [
                const Icon(Icons.notifications_none, color: Colors.white, size: 26),
                Positioned(
                  right: 0,
                  top: 0,
                  child: Container(
                    width: 8,
                    height: 8,
                    decoration: const BoxDecoration(
                      color: Colors.red,
                      shape: BoxShape.circle,
                    ),
                  ),
                ),
              ],
            ),
          ),
          PopupMenuButton(
            icon: const CircleAvatar(
              radius: 18,
              backgroundColor: Colors.white,
              child: Icon(Icons.person, color: Colors.blue, size: 20),
            ),
            itemBuilder: (context) => [
              PopupMenuItem(
                child: const Row(
                  children: [
                    Icon(Icons.person, size: 20),
                    SizedBox(width: 8),
                    Text('Wasifu'),
                  ],
                ),
                onTap: () {
                  Future.delayed(const Duration(milliseconds: 200), () {
                    Navigator.push(
                      context,
                      MaterialPageRoute(builder: (context) => const ProfilePage()),
                    );
                  });
                },
              ),
              if (students.length > 1)
                PopupMenuItem(
                  child: const Row(
                    children: [
                      Icon(Icons.swap_horiz, size: 20),
                      SizedBox(width: 8),
                      Text('Badilisha Mwanafunzi'),
                    ],
                  ),
                  onTap: () {
                    Future.delayed(const Duration(milliseconds: 200), () {
                      _changeStudent();
                    });
                  },
                ),
              PopupMenuItem(
                child: const Row(
                  children: [
                    Icon(Icons.logout, size: 20),
                    SizedBox(width: 8),
                    Text('Toka'),
                  ],
                ),
                onTap: () {
                  Future.delayed(const Duration(milliseconds: 200), () {
                    _logout();
                  });
                },
              ),
            ],
          ),
          const SizedBox(width: 8),
        ],
      ),
      body: isLoading
          ? const Center(child: CircularProgressIndicator())
          : SingleChildScrollView(
              child: Column(
                children: [
                  // Student Card Section
                  Container(
                    margin: const EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      gradient: LinearGradient(
                        colors: [Colors.blue.shade700, Colors.blue.shade500],
                        begin: Alignment.topLeft,
                        end: Alignment.bottomRight,
                      ),
                      borderRadius: BorderRadius.circular(20),
                      boxShadow: [
                        BoxShadow(
                          color: Colors.blue.withOpacity(0.3),
                          blurRadius: 15,
                          offset: const Offset(0, 5),
                        ),
                      ],
                    ),
                    child: student != null
                        ? InkWell(
                            onTap: () {
                              if (currentStudentId != null) {
                                Navigator.push(
                                  context,
                                  MaterialPageRoute(
                                    builder: (context) => StudentDetailsPage(studentId: currentStudentId),
                                  ),
                                );
                              }
                            },
                            borderRadius: BorderRadius.circular(20),
                            child: Padding(
                              padding: const EdgeInsets.all(20),
                              child: Column(
                                children: [
                                  Row(
                                    children: [
                                      Container(
                                        padding: const EdgeInsets.all(12),
                                        decoration: BoxDecoration(
                                          color: Colors.white.withOpacity(0.2),
                                          borderRadius: BorderRadius.circular(16),
                                        ),
                                        child: const Icon(
                                          Icons.person,
                                          color: Colors.white,
                                          size: 32,
                                        ),
                                      ),
                                      const SizedBox(width: 16),
                                      Expanded(
                                        child: Column(
                                          crossAxisAlignment: CrossAxisAlignment.start,
                                          children: [
                                            Text(
                                              studentName,
                                              style: const TextStyle(
                                                color: Colors.white,
                                                fontSize: 22,
                                                fontWeight: FontWeight.bold,
                                              ),
                                            ),
                                            const SizedBox(height: 4),
                                            Text(
                                              studentGrade,
                                              style: TextStyle(
                                                color: Colors.white.withOpacity(0.9),
                                                fontSize: 14,
                                              ),
                                            ),
                                          ],
                                        ),
                                      ),
                                      const Icon(
                                        Icons.chevron_right,
                                        color: Colors.white,
                                        size: 28,
                                      ),
                                    ],
                                  ),
                                  const SizedBox(height: 20),
                                  Row(
                                    children: [
                                      Expanded(
                                        child: _buildQuickStat(
                                          Icons.calendar_today,
                                          'Mahudhurio',
                                          'Hadir',
                                          Colors.green.shade300,
                                        ),
                                      ),
                                      Container(width: 1, height: 40, color: Colors.white.withOpacity(0.3)),
                                      Expanded(
                                        child: _buildQuickStat(
                                          Icons.payment,
                                          'Ada',
                                          '${(feesPaidPercent * 100).toInt()}%',
                                          Colors.orange.shade300,
                                        ),
                                      ),
                                    ],
                                  ),
                                ],
                              ),
                            ),
                          )
                        : Padding(
                            padding: const EdgeInsets.all(20),
                            child: const Text(
                              'Hakuna taarifa za mwanafunzi',
                              style: TextStyle(color: Colors.white),
                            ),
                          ),
                  ),

                  // Quick Stats Cards
                  Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 16),
                    child: Row(
                      children: [
                        Expanded(
                          child: _buildStatCard(
                            'Masomo',
                            '8',
                            Icons.menu_book,
                            Colors.purple,
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: _buildStatCard(
                            'Mitihani',
                            exams.isNotEmpty ? '${exams.length}' : '0',
                            Icons.assignment,
                            Colors.blue,
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: _buildStatCard(
                            'Kazi',
                            '2',
                            Icons.assignment_turned_in,
                            Colors.orange,
                          ),
                        ),
                      ],
                    ),
                  ),

                  const SizedBox(height: 20),

                  // Features Section
                  Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 16),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text(
                          'Huduma',
                          style: TextStyle(
                            fontSize: 20,
                            fontWeight: FontWeight.bold,
                            color: Colors.black87,
                          ),
                        ),
                        const SizedBox(height: 12),
                        GridView.count(
                          crossAxisCount: 3,
                          crossAxisSpacing: 12,
                          mainAxisSpacing: 12,
                          physics: const NeverScrollableScrollPhysics(),
                          shrinkWrap: true,
                          childAspectRatio: 0.9,
                          children: [
                            _FeatureTile(
                              icon: Icons.menu_book,
                              label: 'Masomo',
                              color: Colors.purple,
                              onTap: () {
                                if (student != null && currentStudentId != null) {
                                  Navigator.push(
                                    context,
                                    MaterialPageRoute(
                                      builder: (context) => AcademicsPage(studentId: currentStudentId),
                                    ),
                                  );
                                }
                              },
                            ),
                            _FeatureTile(
                              icon: Icons.assignment,
                              label: 'Kazi',
                              color: Colors.orange,
                              onTap: () {
                                Navigator.push(
                                  context,
                                  MaterialPageRoute(
                                    builder: (context) => const AssignmentsPage(),
                                  ),
                                );
                              },
                            ),
                            _FeatureTile(
                              icon: Icons.check_box,
                              label: 'Mahudhurio',
                              color: Colors.green,
                              onTap: () {
                                Navigator.push(
                                  context,
                                  MaterialPageRoute(
                                    builder: (context) => const AttendancePage(),
                                  ),
                                );
                              },
                            ),
                            _FeatureTile(
                              icon: Icons.payment,
                              label: 'Ada',
                              color: Colors.teal,
                              onTap: () {
                                if (student != null && currentStudentId != null) {
                                  Navigator.push(
                                    context,
                                    MaterialPageRoute(
                                      builder: (context) => FeesPage(studentId: currentStudentId),
                                    ),
                                  );
                                }
                              },
                            ),
                            _FeatureTile(
                              icon: Icons.bar_chart,
                              label: 'Ripoti',
                              color: Colors.indigo,
                              onTap: () {},
                            ),
                            _FeatureTile(
                              icon: Icons.event,
                              label: 'Matukio',
                              color: Colors.pink,
                              onTap: () {},
                            ),
                            _FeatureTile(
                              icon: Icons.emoji_events,
                              label: 'Matokeo',
                              color: Colors.amber,
                              onTap: () {
                                Navigator.push(
                                  context,
                                  MaterialPageRoute(
                                    builder: (context) => const ExamsResultsScreen(),
                                  ),
                                );
                              },
                            ),
                            _FeatureTile(
                              icon: Icons.message,
                              label: 'Ujumbe',
                              color: Colors.cyan,
                              onTap: () {},
                            ),
                            _FeatureTile(
                              icon: Icons.more_horiz,
                              label: 'Zaidi',
                              color: Colors.grey,
                              onTap: () {},
                            ),
                          ],
                        ),
                      ],
                    ),
                  ),

                  const SizedBox(height: 20),

                  // Highlights Carousel
                  Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 16),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text(
                          'Matangazo',
                          style: TextStyle(
                            fontSize: 20,
                            fontWeight: FontWeight.bold,
                            color: Colors.black87,
                          ),
                        ),
                        const SizedBox(height: 12),
                        SizedBox(
                          height: 140,
                          child: PageView(
                            controller: _carouselController,
                            children: const [
                              _HighlightCard(
                                title: 'Mfumo wa SmartAccounting',
                                subtitle: 'Fuata fedha zako kwa urahisi',
                                icon: Icons.account_balance,
                                color: Colors.blue,
                              ),
                              _HighlightCard(
                                title: 'Pata Ripoti Haraka',
                                subtitle: 'Angalia ripoti zako papo hapo',
                                icon: Icons.assessment,
                                color: Colors.green,
                              ),
                              _HighlightCard(
                                title: 'Salama na Kuaminika',
                                subtitle: 'Data yako iko salama kabisa',
                                icon: Icons.security,
                                color: Colors.purple,
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                  ),

                  const SizedBox(height: 100),
                ],
              ),
            ),
      bottomNavigationBar: BottomNavigationBar(
        currentIndex: _selectedIndex,
        onTap: _onBottomNavTap,
        selectedItemColor: Colors.blue.shade700,
        unselectedItemColor: Colors.grey,
        type: BottomNavigationBarType.fixed,
        items: const [
          BottomNavigationBarItem(icon: Icon(Icons.home), label: 'Nyumbani'),
          BottomNavigationBarItem(icon: Icon(Icons.bar_chart), label: 'Matokeo'),
          BottomNavigationBarItem(icon: Icon(Icons.message), label: 'Ujumbe'),
          BottomNavigationBarItem(icon: Icon(Icons.settings), label: 'Mipangilio'),
        ],
      ),
    );
  }

  Widget _buildQuickStat(IconData icon, String label, String value, Color iconColor) {
    return Column(
      children: [
        Icon(icon, color: iconColor, size: 24),
        const SizedBox(height: 8),
        Text(
          value,
          style: const TextStyle(
            color: Colors.white,
            fontSize: 18,
            fontWeight: FontWeight.bold,
          ),
        ),
        const SizedBox(height: 2),
        Text(
          label,
          style: TextStyle(
            color: Colors.white.withOpacity(0.9),
            fontSize: 12,
          ),
        ),
      ],
    );
  }

  Widget _buildStatCard(String label, String value, IconData icon, Color color) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.05),
            blurRadius: 10,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        children: [
          Container(
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: color.withOpacity(0.1),
              borderRadius: BorderRadius.circular(12),
            ),
            child: Icon(icon, color: color, size: 28),
          ),
          const SizedBox(height: 12),
          Text(
            value,
            style: TextStyle(
              fontSize: 24,
              fontWeight: FontWeight.bold,
              color: color,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            label,
            style: TextStyle(
              fontSize: 12,
              color: Colors.grey.shade600,
            ),
          ),
        ],
      ),
    );
  }
}

class _FeatureTile extends StatelessWidget {
  final IconData icon;
  final String label;
  final VoidCallback onTap;
  final Color color;

  const _FeatureTile({
    required this.icon,
    required this.label,
    required this.onTap,
    required this.color,
  });

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(16),
      child: Container(
        decoration: BoxDecoration(
          gradient: LinearGradient(
            colors: [color.withOpacity(0.1), color.withOpacity(0.05)],
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
          ),
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: color.withOpacity(0.2)),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withOpacity(0.03),
              blurRadius: 8,
              offset: const Offset(0, 2),
            ),
          ],
        ),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: color.withOpacity(0.15),
                borderRadius: BorderRadius.circular(12),
              ),
              child: Icon(icon, size: 28, color: color),
            ),
            const SizedBox(height: 10),
            Text(
              label,
              textAlign: TextAlign.center,
              style: TextStyle(
                fontSize: 12,
                fontWeight: FontWeight.w600,
                color: Colors.grey.shade800,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _HighlightCard extends StatelessWidget {
  final String title;
  final String subtitle;
  final IconData icon;
  final Color color;
  
  const _HighlightCard({
    required this.title,
    required this.subtitle,
    required this.icon,
    required this.color,
  });

  static Color _darkenColor(Color color, double amount) {
    assert(amount >= 0 && amount <= 1);
    final hsl = HSLColor.fromColor(color);
    final lightness = (hsl.lightness - amount).clamp(0.0, 1.0);
    return hsl.withLightness(lightness).toColor();
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.symmetric(horizontal: 4),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: [_darkenColor(color, 0.3), color],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: color.withOpacity(0.3),
            blurRadius: 10,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Padding(
        padding: const EdgeInsets.all(20),
        child: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: Colors.white.withOpacity(0.2),
                borderRadius: BorderRadius.circular(12),
              ),
              child: Icon(icon, color: Colors.white, size: 32),
            ),
            const SizedBox(width: 16),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Text(
                    title,
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 16,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  const SizedBox(height: 6),
                  Text(
                    subtitle,
                    style: TextStyle(
                      color: Colors.white.withOpacity(0.9),
                      fontSize: 13,
                    ),
                  ),
                ],
              ),
            ),
            Icon(Icons.chevron_right, color: Colors.white.withOpacity(0.8)),
          ],
        ),
      ),
    );
  }
}

// Academics Page - Keep existing implementation
class AcademicsPage extends StatefulWidget {
  final int studentId;
  const AcademicsPage({super.key, required this.studentId});

  @override
  _AcademicsPageState createState() => _AcademicsPageState();
}

class _AcademicsPageState extends State<AcademicsPage> with TickerProviderStateMixin {
  late TabController _tabController;
  List<dynamic> subjects = [];
  List<dynamic> exams = [];
  bool isLoadingSubjects = true;
  bool isLoadingExams = true;

  @override
  void initState() {
    _tabController = TabController(length: 4, vsync: this);
    super.initState();
    _loadSubjects();
    _loadExams();
  }

  Future<void> _loadSubjects() async {
    final data = await ParentApiService.getStudentSubjects(widget.studentId);
    if (mounted) {
      setState(() {
        subjects = data ?? [];
        isLoadingSubjects = false;
      });
    }
  }

  Future<void> _loadExams() async {
    final data = await ParentApiService.getStudentExams(widget.studentId);
    if (mounted) {
      setState(() {
        exams = data ?? [];
        isLoadingExams = false;
      });
    }
  }

  String _calculateOverallAverage(List<dynamic> exams) {
    if (exams.isEmpty) return '0.0';
    final validExams = exams.where((e) {
      final avg = e['average_raw_marks'] ?? e['average'];
      return avg != null && (avg is num) && avg > 0;
    }).toList();
    if (validExams.isEmpty) return '0.0';
    final total = validExams.map((e) {
      final avg = e['average_raw_marks'] ?? e['average'];
      return (avg as num).toDouble();
    }).reduce((a, b) => a + b);
    return (total / validExams.length).toStringAsFixed(1);
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.grey.shade50,
      appBar: AppBar(
        elevation: 0,
        flexibleSpace: Container(
          decoration: BoxDecoration(
            gradient: LinearGradient(
              colors: [Colors.blue.shade700, Colors.blue.shade500],
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
            ),
          ),
        ),
        title: const Text(
          "Academics",
          style: TextStyle(
            fontWeight: FontWeight.bold,
            fontSize: 22,
            letterSpacing: 0.5,
          ),
        ),
        bottom: PreferredSize(
          preferredSize: const Size.fromHeight(50),
          child: Container(
            decoration: BoxDecoration(
              gradient: LinearGradient(
                colors: [Colors.blue.shade700, Colors.blue.shade500],
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
              ),
            ),
            child: TabBar(
              controller: _tabController,
              labelColor: Colors.white,
              unselectedLabelColor: Colors.white70,
              indicatorColor: Colors.white,
              indicatorWeight: 3,
              labelStyle: const TextStyle(
                fontWeight: FontWeight.bold,
                fontSize: 13,
              ),
              tabs: const [
                Tab(icon: Icon(Icons.menu_book, size: 20), text: "Subjects"),
                Tab(icon: Icon(Icons.assignment, size: 20), text: "Exams"),
                Tab(icon: Icon(Icons.article, size: 20), text: "Assignments"),
                Tab(icon: Icon(Icons.library_books, size: 20), text: "Materials"),
              ],
            ),
          ),
        ),
      ),
      body: TabBarView(
        controller: _tabController,
        children: [
          // Subjects Tab
          isLoadingSubjects
              ? Center(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      CircularProgressIndicator(
                        valueColor: AlwaysStoppedAnimation<Color>(Colors.blue.shade700),
                      ),
                      const SizedBox(height: 16),
                      Text(
                        'Inapakia masomo...',
                        style: TextStyle(
                          color: Colors.grey.shade600,
                          fontSize: 14,
                        ),
                      ),
                    ],
                  ),
                )
              : subjects.isEmpty
                  ? Center(
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Container(
                            padding: const EdgeInsets.all(24),
                            decoration: BoxDecoration(
                              color: Colors.grey.shade100,
                              shape: BoxShape.circle,
                            ),
                            child: Icon(
                              Icons.menu_book_outlined,
                              size: 64,
                              color: Colors.grey.shade400,
                            ),
                          ),
                          const SizedBox(height: 24),
                          Text(
                            'Hakuna masomo yaliyopatikana',
                            style: TextStyle(
                              fontSize: 16,
                              color: Colors.grey.shade600,
                              fontWeight: FontWeight.w500,
                            ),
                          ),
                        ],
                      ),
                    )
                  : Container(
                      decoration: BoxDecoration(
                        gradient: LinearGradient(
                          begin: Alignment.topCenter,
                          end: Alignment.bottomCenter,
                          colors: [
                            Colors.blue.shade50,
                            Colors.grey.shade50,
                          ],
                        ),
                      ),
                      child: ListView(
                        padding: const EdgeInsets.all(16),
                        children: [
                          Container(
                            padding: const EdgeInsets.all(20),
                            decoration: BoxDecoration(
                              gradient: LinearGradient(
                                colors: [Colors.blue.shade700, Colors.blue.shade500],
                                begin: Alignment.topLeft,
                                end: Alignment.bottomRight,
                              ),
                              borderRadius: BorderRadius.circular(16),
                              boxShadow: [
                                BoxShadow(
                                  color: Colors.blue.withOpacity(0.3),
                                  blurRadius: 15,
                                  offset: const Offset(0, 5),
                                ),
                              ],
                            ),
                            child: Row(
                              children: [
                                Container(
                                  padding: const EdgeInsets.all(12),
                                  decoration: BoxDecoration(
                                    color: Colors.white.withOpacity(0.2),
                                    borderRadius: BorderRadius.circular(12),
                                  ),
                                  child: const Icon(
                                    Icons.menu_book,
                                    color: Colors.white,
                                    size: 28,
                                  ),
                                ),
                                const SizedBox(width: 16),
                                Expanded(
                                  child: Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      const Text(
                                        'Masomo Yako',
                                        style: TextStyle(
                                          color: Colors.white,
                                          fontSize: 20,
                                          fontWeight: FontWeight.bold,
                                        ),
                                      ),
                                      const SizedBox(height: 4),
                                      Text(
                                        '${subjects.length} ${subjects.length == 1 ? 'Somo' : 'Masomo'}',
                                        style: TextStyle(
                                          color: Colors.white.withOpacity(0.9),
                                          fontSize: 14,
                                        ),
                                      ),
                                    ],
                                  ),
                                ),
                              ],
                            ),
                          ),
                          const SizedBox(height: 20),
                          GridView.builder(
                            shrinkWrap: true,
                            physics: const NeverScrollableScrollPhysics(),
                            gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                              crossAxisCount: 2,
                              crossAxisSpacing: 12,
                              mainAxisSpacing: 12,
                              childAspectRatio: 1.1,
                            ),
                            itemCount: subjects.length,
                            itemBuilder: (context, index) {
                              final subject = subjects[index];
                              final subjectName = subject['name'] ?? 'Unknown Subject';
                              
                              final colorPairs = <List<Color>>[
                                <Color>[Colors.purple.shade400, Colors.purple.shade600],
                                <Color>[Colors.blue.shade400, Colors.blue.shade600],
                                <Color>[Colors.green.shade400, Colors.green.shade600],
                                <Color>[Colors.orange.shade400, Colors.orange.shade600],
                                <Color>[Colors.red.shade400, Colors.red.shade600],
                                <Color>[Colors.teal.shade400, Colors.teal.shade600],
                                <Color>[Colors.indigo.shade400, Colors.indigo.shade600],
                                <Color>[Colors.pink.shade400, Colors.pink.shade600],
                              ];
                              
                              final colorPair = colorPairs[index % colorPairs.length];
                              
                              return _buildSubjectCard(
                                subjectName,
                                colorPair[0],
                                colorPair[1],
                                index,
                              );
                            },
                          ),
                        ],
                      ),
                    ),

          // Exams Tab
          isLoadingExams
              ? const Center(child: CircularProgressIndicator())
              : exams.isEmpty
                  ? Center(
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(Icons.assignment_outlined, size: 64, color: Colors.grey.shade400),
                          const SizedBox(height: 16),
                          Text(
                            'Hakuna matokeo ya mitihani',
                            style: TextStyle(fontSize: 16, color: Colors.grey.shade600),
                          ),
                        ],
                      ),
                    )
                  : Container(
                      decoration: BoxDecoration(
                        gradient: LinearGradient(
                          begin: Alignment.topCenter,
                          end: Alignment.bottomCenter,
                          colors: [
                            Colors.blue.shade50,
                            Colors.grey.shade50,
                          ],
                        ),
                      ),
                      child: ListView(
                        padding: const EdgeInsets.all(16),
                        children: [
                          Container(
                            padding: const EdgeInsets.all(20),
                            decoration: BoxDecoration(
                              gradient: LinearGradient(
                                colors: [Colors.blue.shade700, Colors.blue.shade500],
                                begin: Alignment.topLeft,
                                end: Alignment.bottomRight,
                              ),
                              borderRadius: BorderRadius.circular(16),
                              boxShadow: [
                                BoxShadow(
                                  color: Colors.blue.withOpacity(0.3),
                                  blurRadius: 15,
                                  offset: const Offset(0, 5),
                                ),
                              ],
                            ),
                            child: Row(
                              children: [
                                Container(
                                  padding: const EdgeInsets.all(12),
                                  decoration: BoxDecoration(
                                    color: Colors.white.withOpacity(0.2),
                                    borderRadius: BorderRadius.circular(12),
                                  ),
                          