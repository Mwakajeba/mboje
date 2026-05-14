import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'home/home_screen.dart'; // For HomePage

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
      // Navigate to home and clear the navigation stack
      Navigator.of(context).pushAndRemoveUntil(
        MaterialPageRoute(builder: (context) => const HomePage()),
        (route) => false,
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFFFFBF0), // Rangi ya maziwa (milk/cream color)
      appBar: AppBar(
        title: const Text('Select Student'),
        backgroundColor: Colors.blue,
        foregroundColor: Colors.white,
        automaticallyImplyLeading: false, // Remove back button
        elevation: 0,
      ),
      body: Container(
        color: const Color(0xFFFFFBF0), // Rangi ya maziwa (milk/cream color)
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
                    color: Color(0xFF2C3E50), // Dark gray for visibility on milk background
                  ),
                  textAlign: TextAlign.center,
                ),
                const SizedBox(height: 10),
                Text(
                  'You have multiple students. Please select which student you want to view.',
                  style: TextStyle(
                    fontSize: 16,
                    color: const Color(0xFF5D6D7E).withOpacity(0.8), // Medium gray
                  ),
                  textAlign: TextAlign.center,
                ),
                const SizedBox(height: 30),
                Expanded(
                  child: widget.students.isEmpty
                      ? const Center(
                          child: Text(
                            'No students found',
                            style: TextStyle(color: Color(0xFF5D6D7E)), // Gray for visibility on milk background
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
                              
                              // Convert student ID to int (handles both int and String from API)
                              final studentId = student['id'] is int 
                                  ? student['id'] as int
                                  : (student['id'] != null 
                                      ? int.tryParse(student['id'].toString()) ?? 0
                                      : 0);
                              final isSelected = _selectedStudentId == studentId;
                              
                              // Safely extract student name
                              final studentName = student['name']?.toString() ?? 'Student';
                              
                              // Safely extract class name
                              String className = 'N/A';
                              if (student['class'] != null) {
                                if (student['class'] is Map) {
                                  className = student['class']?['name']?.toString() ?? 'N/A';
                                } else if (student['class'] is String) {
                                  className = student['class'] as String;
                                }
                              }
                              
                              // Safely extract stream name
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
                              // Handle any errors gracefully
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

