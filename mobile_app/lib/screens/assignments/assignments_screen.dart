import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../../services/parent_api_service.dart';
import '../../providers/language_provider.dart';
import '../../providers/theme_provider.dart';
import '../assignments/assignment_detail_screen.dart';

class AssignmentsPage extends StatefulWidget {
  const AssignmentsPage({super.key});

  @override
  State<AssignmentsPage> createState() => _AssignmentsPageState();
}

class _AssignmentsPageState extends State<AssignmentsPage> {
  bool isLoading = true;
  int? studentId;
  
  Map<String, dynamic>? assignmentsData;

  @override
  void initState() {
    super.initState();
    _loadStudentId();
  }

  Future<void> _loadStudentId() async {
    final prefs = await SharedPreferences.getInstance();
    final id = prefs.getInt('selected_student_id');
    if (id != null) {
      setState(() {
        studentId = id;
      });
      _loadAssignments();
    } else {
      setState(() {
        isLoading = false;
      });
    }
  }

  Future<void> _loadAssignments() async {
    if (studentId == null) return;
    
    setState(() {
      isLoading = true;
    });

    try {
      final data = await ParentApiService.getStudentAssignments(studentId!);
      if (mounted) {
        setState(() {
          assignmentsData = data;
          isLoading = false;
        });
      }
    } catch (e) {
      print('Error loading assignments: $e');
      if (mounted) {
        setState(() {
          isLoading = false;
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final languageProvider = Provider.of<LanguageProvider>(context);
    final themeProvider = Provider.of<ThemeProvider>(context);
    final trans = AppTranslations(languageProvider.currentLanguage);
    final isDark = themeProvider.isDarkMode;
    
    return Scaffold(
      backgroundColor: isDark ? const Color(0xFF101115) : Colors.grey.shade50,
      appBar: AppBar(
        elevation: 0,
        flexibleSpace: Container(
          decoration: BoxDecoration(
            gradient: LinearGradient(
              colors: isDark 
                  ? [const Color(0xFF16181F), const Color(0xFF1A1D24)]
                  : [Colors.orange.shade700, Colors.orange.shade500],
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
            ),
          ),
        ),
        title: Text(
          trans.get('homework'),
          style: const TextStyle(
            fontWeight: FontWeight.bold,
            fontSize: 22,
            letterSpacing: 0.5,
            color: Colors.white,
          ),
        ),
        foregroundColor: Colors.white,
        actions: [
          IconButton(
            onPressed: () {
              _loadAssignments();
            },
            icon: const Icon(Icons.refresh),
            tooltip: trans.get('refresh'),
          )
        ],
      ),
      body: isLoading
          ? const Center(child: CircularProgressIndicator())
          : RefreshIndicator(
              onRefresh: () async {
                await _loadAssignments();
              },
              child: Container(
                decoration: BoxDecoration(
                  gradient: LinearGradient(
                    begin: Alignment.topCenter,
                    end: Alignment.bottomCenter,
                    colors: [
                      Colors.orange.shade50,
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
                          colors: [Colors.orange.shade600, Colors.orange.shade400],
                          begin: Alignment.topLeft,
                          end: Alignment.bottomRight,
                        ),
                        borderRadius: BorderRadius.circular(16),
                        boxShadow: [
                          BoxShadow(
                            color: Colors.orange.withOpacity(0.3),
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
                              Icons.article,
                              color: Colors.white,
                              size: 28,
                            ),
                          ),
                          const SizedBox(width: 16),
                          Expanded(
                            child: Text(
                              trans.get('homework'),
                              style: const TextStyle(
                                color: Colors.white,
                                fontSize: 20,
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 20),
                    _buildAssignmentsList(),
                  ],
                ),
              ),
            ),
    );
  }

  Widget _buildAssignmentsList() {
    final languageProvider = Provider.of<LanguageProvider>(context);
    final themeProvider = Provider.of<ThemeProvider>(context);
    final trans = AppTranslations(languageProvider.currentLanguage);
    final isDark = themeProvider.isDarkMode;
    
    if (assignmentsData == null) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.assignment_outlined, size: 64, color: isDark ? Colors.grey.shade600 : Colors.grey.shade400),
            const SizedBox(height: 16),
            Text(
              trans.get('no_homework'),
              style: TextStyle(
                fontSize: 16,
                color: isDark ? Colors.grey.shade400 : Colors.grey.shade600,
              ),
            ),
          ],
        ),
      );
    }

    final upcoming = List<dynamic>.from(assignmentsData!['upcoming'] ?? []);
    final dueSoon = List<dynamic>.from(assignmentsData!['due_soon'] ?? []);
    final submitted = List<dynamic>.from(assignmentsData!['submitted'] ?? []);
    final marked = List<dynamic>.from(assignmentsData!['marked'] ?? []);
    final overdue = List<dynamic>.from(assignmentsData!['overdue'] ?? []);

    if (upcoming.isEmpty && dueSoon.isEmpty && submitted.isEmpty && marked.isEmpty && overdue.isEmpty) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.assignment_outlined, size: 64, color: isDark ? Colors.grey.shade600 : Colors.grey.shade400),
            const SizedBox(height: 16),
            Text(
              trans.get('no_homework'),
              style: TextStyle(
                fontSize: 16,
                color: isDark ? Colors.grey.shade400 : Colors.grey.shade600,
              ),
            ),
          ],
        ),
      );
    }

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        if (overdue.isNotEmpty) ...[
          _buildSectionHeader(trans.get('overdue'), Colors.red),
          ...overdue.map((item) => _buildAssignmentCardFromData(item, Colors.red, Icons.warning)),
          const SizedBox(height: 16),
        ],
        if (dueSoon.isNotEmpty) ...[
          _buildSectionHeader(trans.get('due_soon'), Colors.orange),
          ...dueSoon.map((item) => _buildAssignmentCardFromData(item, Colors.orange, Icons.schedule)),
          const SizedBox(height: 16),
        ],
        if (upcoming.isNotEmpty) ...[
          _buildSectionHeader(trans.get('upcoming_assignments'), Colors.blue),
          ...upcoming.map((item) => _buildAssignmentCardFromData(item, Colors.blue, Icons.calendar_today)),
          const SizedBox(height: 16),
        ],
        if (submitted.isNotEmpty) ...[
          _buildSectionHeader(trans.get('submitted_assignments'), Colors.green),
          ...submitted.map((item) => _buildAssignmentCardFromData(item, Colors.green, Icons.check_circle)),
          const SizedBox(height: 16),
        ],
        if (marked.isNotEmpty) ...[
          _buildSectionHeader(trans.get('marked_assignments'), Colors.purple),
          ...marked.map((item) => _buildAssignmentCardFromData(item, Colors.purple, Icons.grade)),
        ],
      ],
    );
  }

  Widget _buildSectionHeader(String title, Color color) {
    final themeProvider = Provider.of<ThemeProvider>(context);
    final isDark = themeProvider.isDarkMode;
    return Padding(
      padding: const EdgeInsets.only(bottom: 12, top: 8),
      child: Row(
        children: [
          Container(
            width: 4,
            height: 20,
            decoration: BoxDecoration(
              color: color,
              borderRadius: BorderRadius.circular(2),
            ),
          ),
          const SizedBox(width: 8),
          Text(
            title,
            style: TextStyle(
              fontSize: 16,
              fontWeight: FontWeight.bold,
              color: isDark ? (color.withOpacity(0.9)) : color,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildAssignmentCardFromData(Map<String, dynamic> item, Color statusColor, IconData icon) {
    final languageProvider = Provider.of<LanguageProvider>(context);
    final trans = AppTranslations(languageProvider.currentLanguage);
    final title = item['title'] ?? item['subject'] ?? trans.get('assignments');
    final assignedDate = item['assigned_date'] ?? '';
    // Use due_datetime if available (includes time), otherwise use due_date
    final dueDate = item['due_datetime'] ?? item['due_date'] ?? item['dueDate'] ?? '';
    final status = item['status'] ?? '';
    final subject = item['subject_name'] ?? item['subject'] ?? '';

    return _buildAssignmentCard(
      title,
      assignedDate,
      dueDate,
      status,
      statusColor,
      icon,
      item, // Pass full assignment data
    );
  }

  String _getDueText(String? dueDateString, String? dueTimeString) {
    final languageProvider = Provider.of<LanguageProvider>(context, listen: false);
    final trans = AppTranslations(languageProvider.currentLanguage);
    final isSwahili = languageProvider.currentLanguage == 'sw';
    
    if (dueDateString == null || dueDateString.isEmpty) return 'N/A';

    try {
      // Parse the date - handle Y-m-d format
      DateTime dueDate;
      if (dueDateString.contains('-')) {
        // Format: Y-m-d or Y-m-d H:i:s
        final parts = dueDateString.split(' ');
        final dateParts = parts[0].split('-');
        if (dateParts.length >= 3) {
          dueDate = DateTime(
            int.parse(dateParts[0]),
            int.parse(dateParts[1]),
            int.parse(dateParts[2]),
          );
        } else {
          dueDate = DateTime.parse(dueDateString);
        }
      } else {
        dueDate = DateTime.parse(dueDateString);
      }
      
      // If due_time is available, add it to the date
      if (dueTimeString != null && dueTimeString.isNotEmpty) {
        try {
          final timeParts = dueTimeString.split(':');
          if (timeParts.length >= 2) {
            dueDate = DateTime(
              dueDate.year,
              dueDate.month,
              dueDate.day,
              int.tryParse(timeParts[0]) ?? 0,
              int.tryParse(timeParts[1]) ?? 0,
            );
          }
        } catch (e) {
          // If time parsing fails, use date only
        }
      }

      final now = DateTime.now();
      // Normalize both dates to start of day for accurate day calculation
      final dueDateNormalized = DateTime(dueDate.year, dueDate.month, dueDate.day);
      final nowNormalized = DateTime(now.year, now.month, now.day);
      
      // Calculate difference in whole days
      final difference = dueDateNormalized.difference(nowNormalized).inDays;
      final absDifference = difference.abs();
      
      if (difference < 0) {
        return isSwahili 
            ? 'Imeisha $absDifference ${absDifference == 1 ? 'siku' : 'siku'} zilizopita'
            : 'Overdue by $absDifference ${absDifference == 1 ? 'day' : 'days'}';
      } else if (difference == 0) {
        // Check if it's today but past time
        if (dueDate.isBefore(now)) {
          return isSwahili ? 'Imeisha leo' : 'Overdue today';
        } else {
          return isSwahili ? 'Leo' : 'Today';
        }
      } else if (difference == 1) {
        return isSwahili ? 'Kesho' : 'Tomorrow';
      } else {
        return isSwahili ? 'Siku $difference' : '$difference days';
      }
    } catch (e) {
      print('Error parsing date: $e');
      return dueDateString;
    }
  }

  Widget _buildAssignmentCard(String title, String assignedDate, String dueDate, String status, Color statusColor, IconData icon, Map<String, dynamic> assignmentData) {
    final languageProvider = Provider.of<LanguageProvider>(context);
    final trans = AppTranslations(languageProvider.currentLanguage);
    
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
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
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          onTap: () {
            try {
              Navigator.push(
                context,
                MaterialPageRoute(
                  builder: (context) => AssignmentDetailScreen(assignment: assignmentData),
                ),
              );
            } catch (e) {
              print('Error navigating to assignment details: $e');
              final languageProvider = Provider.of<LanguageProvider>(context, listen: false);
              final trans = AppTranslations(languageProvider.currentLanguage);
              ScaffoldMessenger.of(context).showSnackBar(
                SnackBar(
                  content: Text(
                    languageProvider.currentLanguage == 'sw'
                        ? 'Hitilafu: Hatuwezi kufungua maelezo ya kazi hii'
                        : 'Error: Cannot open assignment details',
                  ),
                  backgroundColor: Colors.red,
                ),
              );
            }
          },
          borderRadius: BorderRadius.circular(16),
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Row(
              children: [
                Container(
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: statusColor.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Icon(icon, color: statusColor, size: 24),
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        title,
                        style: const TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                      const SizedBox(height: 8),
                      // Created Date
                      if (assignedDate.isNotEmpty) ...[
                        Row(
                          children: [
                            Icon(Icons.add_circle_outline, size: 14, color: Colors.grey.shade600),
                            const SizedBox(width: 4),
                            Text(
                              '${trans.get('created_date')}: ${_formatDate(assignedDate)}',
                              style: TextStyle(
                                fontSize: 12,
                                color: Colors.grey.shade600,
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 4),
                      ],
                      // Due Date
                      Row(
                        children: [
                          Icon(Icons.calendar_today, size: 14, color: Colors.grey.shade600),
                          const SizedBox(width: 4),
                          Text(
                            '${trans.get('due_date_short')}: ${_formatDate(dueDate)}',
                            style: TextStyle(
                              fontSize: 12,
                              color: Colors.grey.shade600,
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                  decoration: BoxDecoration(
                    color: statusColor.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(20),
                    border: Border.all(color: statusColor, width: 1.5),
                  ),
                  child: Text(
                    trans.get('view_details'),
                    style: TextStyle(
                      fontSize: 12,
                      fontWeight: FontWeight.bold,
                      color: statusColor,
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

  String _formatDate(String? dateString) {
    if (dateString == null || dateString.isEmpty) return 'N/A';
    try {
      DateTime date;
      // Handle Y-m-d format (date only) or Y-m-d H:i:s format
      if (dateString.contains('-')) {
        final parts = dateString.split(' ');
        final dateParts = parts[0].split('-');
        if (dateParts.length == 3) {
          // Parse as local date (no timezone conversion)
          int hour = 0, minute = 0;
          if (parts.length > 1 && parts[1].contains(':')) {
            final timeParts = parts[1].split(':');
            if (timeParts.length >= 2) {
              hour = int.tryParse(timeParts[0]) ?? 0;
              minute = int.tryParse(timeParts[1]) ?? 0;
            }
          }
          date = DateTime(
            int.parse(dateParts[0]),
            int.parse(dateParts[1]),
            int.parse(dateParts[2]),
            hour,
            minute,
          );
        } else {
          date = DateTime.parse(dateString);
        }
      } else {
        date = DateTime.parse(dateString);
      }
      
      final dayNames = ['Jumapili', 'Jumatatu', 'Jumanne', 'Jumatano', 'Alhamisi', 'Ijumaa', 'Jumamosi'];
      final monthNames = ['', 'Jan', 'Feb', 'Mac', 'Apr', 'Mei', 'Jun', 'Jul', 'Ago', 'Sep', 'Okt', 'Nov', 'Des'];
      return '${dayNames[date.weekday % 7]}, ${date.day} ${monthNames[date.month]} ${date.year}';
    } catch (e) {
      print('Error formatting date: $dateString - $e');
      return dateString;
    }
  }
}
