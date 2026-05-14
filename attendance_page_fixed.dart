import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'main.dart'; // For ParentApiService
import 'language_provider.dart';

class AttendancePage extends StatefulWidget {
  const AttendancePage({super.key});

  @override
  State<AttendancePage> createState() => _AttendancePageState();
}

class _AttendancePageState extends State<AttendancePage> {
  String _viewType = 'Weekly'; // Weekly or Monthly
  Map<String, dynamic>? attendanceStats;
  List<dynamic>? attendanceRecords;
  Map<String, dynamic>? studentInfo;
  bool isLoading = true;
  int? studentId;

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
      _loadAttendanceData();
    } else {
      setState(() {
        isLoading = false;
      });
    }
  }

  Future<void> _loadAttendanceData() async {
    if (studentId == null) return;
    
    setState(() {
      isLoading = true;
    });

    try {
      // Load attendance stats and records in parallel
      final statsData = await ParentApiService.getStudentAttendanceStats(studentId!);
      final recordsData = await ParentApiService.getStudentAttendance(studentId!);
      final studentData = await ParentApiService.getStudentDetails(studentId!);

      if (mounted) {
        setState(() {
          attendanceStats = statsData;
          attendanceRecords = recordsData;
          studentInfo = studentData;
          isLoading = false;
        });
      }
    } catch (e) {
      print('Error loading attendance data: $e');
      if (mounted) {
        setState(() {
          isLoading = false;
        });
      }
    }
  }

  // Helper to convert RGB map to Color
  Color _getColorFromMap(Map<String, dynamic>? colorMap) {
    if (colorMap == null) return Colors.blue;
    final r = colorMap['r'] ?? 19;
    final g = colorMap['g'] ?? 127;
    final b = colorMap['b'] ?? 236;
    return Color.fromRGBO(r, g, b, 1.0);
  }

  @override
  Widget build(BuildContext context) {
    final languageProvider = Provider.of<LanguageProvider>(context);
    final trans = AppTranslations(languageProvider.currentLanguage);

    return Scaffold(
      backgroundColor: const Color(0xFFF6F7F8),
      appBar: AppBar(
        backgroundColor: Colors.white.withValues(alpha: 0.9),
        elevation: 0,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back, color: Color(0xFF111418)),
          onPressed: () => Navigator.pop(context),
        ),
        title: Text(
          trans.get('attendance_title'),
          style: const TextStyle(
            color: Color(0xFF111418),
            fontSize: 18,
            fontWeight: FontWeight.bold,
          ),
        ),
        centerTitle: true,
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh, color: Color(0xFF111418)),
            onPressed: () {
              _loadAttendanceData();
            },
          ),
        ],
      ),
      body: isLoading
          ? const Center(child: CircularProgressIndicator())
          : studentId == null
              ? Center(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Icon(Icons.person_off, size: 64, color: Colors.grey.shade400),
                      const SizedBox(height: 16),
                      Text(
                        'No student selected',
                        style: TextStyle(
                          color: Colors.grey.shade600,
                          fontSize: 16,
                        ),
                      ),
                    ],
                  ),
                )
              : SingleChildScrollView(
              child: Column(
                children: [
                  // Student Info Card
                  Padding(
                    padding: const EdgeInsets.all(16),
                    child: Container(
                      width: double.infinity,
                      padding: const EdgeInsets.all(16),
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(color: Colors.grey.shade200),
                        boxShadow: [
                          BoxShadow(
                            color: Colors.black.withValues(alpha: 0.05),
                            blurRadius: 4,
                            offset: const Offset(0, 2),
                          ),
                        ],
                      ),
                      child: Row(
                        children: [
                          // Avatar
                          CircleAvatar(
                            radius: 28,
                            backgroundColor: Colors.grey.shade200,
                            child: Icon(
                              Icons.person,
                              size: 32,
                              color: Colors.grey.shade600,
                            ),
                          ),
                          const SizedBox(width: 16),
                          // Name and Grade
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  studentInfo?['full_name'] ?? 'Student',
                                  style: const TextStyle(
                                    fontSize: 18,
                                    fontWeight: FontWeight.bold,
                                    color: Color(0xFF111418),
                                  ),
                                ),
                                const SizedBox(height: 4),
                                Text(
                                  '${studentInfo?['class']?['name'] ?? 'N/A'} • ${studentInfo?['stream']?['name'] ?? ''}',
                                  style: TextStyle(
                                    fontSize: 14,
                                    color: Colors.grey.shade600,
                                    fontWeight: FontWeight.w500,
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),

                  // Stats Cards Row
                  if (attendanceStats != null && attendanceStats!['stats'] != null)
                    Padding(
                      padding: const EdgeInsets.symmetric(horizontal: 16),
                      child: Row(
                        children: [
                          Expanded(
                            child: _buildStatCard(
                              icon: Icons.percent,
                              iconColor: const Color(0xFF137FEC),
                              label: 'Rate',
                              value: '${((attendanceStats!['stats']?['rate'] ?? 0) as num).toInt()}%',
                              progress: ((attendanceStats!['stats']?['rate'] ?? 0) as num).toDouble() / 100,
                              progressColor: const Color(0xFF137FEC),
                            ),
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            child: _buildStatCard(
                              icon: Icons.event_busy,
                              iconColor: Colors.orange,
                              label: 'Absences',
                              value: '${((attendanceStats!['stats']?['absences'] ?? 0) as num).toInt()}',
                              subtitle: '${((attendanceStats!['stats']?['unexcused'] ?? 0) as num).toInt()} Unexcused',
                              subtitleColor: Colors.orange,
                            ),
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            child: _buildStatCard(
                              icon: Icons.schedule,
                              iconColor: Colors.yellow.shade700,
                              label: 'Tardies',
                              value: '${((attendanceStats!['stats']?['tardies'] ?? 0) as num).toInt()}',
                              subtitle: attendanceStats!['stats']?['last_tardy'] != null
                                  ? 'Last: ${attendanceStats!['stats']?['last_tardy']}'
                                  : 'None',
                            ),
                          ),
                        ],
                      ),
                    ),

                  const SizedBox(height: 16),

                  // Weekly/Monthly View Toggle
                  Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 16),
                    child: Container(
                      padding: const EdgeInsets.all(4),
                      decoration: BoxDecoration(
                        color: const Color(0xFFF6F7F8),
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: Row(
                        children: [
                          Expanded(
                            child: _buildToggleButton('Weekly', _viewType == 'Weekly'),
                          ),
                          Expanded(
                            child: _buildToggleButton('Monthly', _viewType == 'Monthly'),
                          ),
                        ],
                      ),
                    ),
                  ),

                  const SizedBox(height: 16),

                  // Chart Card
                  if (attendanceStats != null && attendanceStats!['weekly_data'] != null)
                    Padding(
                      padding: const EdgeInsets.symmetric(horizontal: 16),
                      child: Container(
                        padding: const EdgeInsets.all(16),
                        decoration: BoxDecoration(
                          color: Colors.white,
                          borderRadius: BorderRadius.circular(12),
                          border: Border.all(color: Colors.grey.shade200),
                          boxShadow: [
                            BoxShadow(
                              color: Colors.black.withValues(alpha: 0.05),
                              blurRadius: 4,
                              offset: const Offset(0, 2),
                            ),
                          ],
                        ),
                        child: Column(
                          children: [
                            SizedBox(
                              height: 128,
                              child: Row(
                                crossAxisAlignment: CrossAxisAlignment.end,
                                mainAxisAlignment: MainAxisAlignment.spaceAround,
                                children: (attendanceStats!['weekly_data'] as List).map((dayData) {
                                  final heightPercent = (dayData['height'] ?? 0.0) as double;
                                  final colorName = dayData['color'] ?? 'blue';
                                  final isAbsent = heightPercent == 0.0;
                                  const maxHeight = 100.0;
                                  final barHeight = isAbsent ? 0.0 : (heightPercent / 100.0) * maxHeight;
                                  
                                  // Convert color name to Color
                                  Color dayColor;
                                  switch (colorName) {
                                    case 'blue':
                                      dayColor = Colors.blue;
                                      break;
                                    case 'yellow':
                                      dayColor = Colors.yellow;
                                      break;
                                    case 'orange':
                                      dayColor = Colors.orange;
                                      break;
                                    default:
                                      dayColor = Colors.blue;
                                  }
                                  
                                  return Expanded(
                                    child: Padding(
                                      padding: const EdgeInsets.symmetric(horizontal: 4),
                                      child: Column(
                                        mainAxisAlignment: MainAxisAlignment.end,
                                        children: [
                                          Container(
                                            height: maxHeight,
                                            width: double.infinity,
                                            decoration: BoxDecoration(
                                              color: dayColor.withValues(alpha: 0.2),
                                              borderRadius: const BorderRadius.vertical(
                                                top: Radius.circular(4),
                                              ),
                                            ),
                                            child: Stack(
                                              children: [
                                                if (!isAbsent)
                                                  Positioned(
                                                    bottom: 0,
                                                    left: 0,
                                                    right: 0,
                                                    child: Container(
                                                      height: barHeight,
                                                      decoration: BoxDecoration(
                                                        color: dayColor,
                                                        borderRadius: const BorderRadius.vertical(
                                                          top: Radius.circular(4),
                                                        ),
                                                      ),
                                                    ),
                                                  )
                                                else
                                                  Positioned(
                                                    bottom: 0,
                                                    left: 0,
                                                    right: 0,
                                                    child: Container(
                                                      height: 2,
                                                      decoration: BoxDecoration(
                                                        color: Colors.orange,
                                                        borderRadius: BorderRadius.circular(1),
                                                      ),
                                                    ),
                                                  ),
                                              ],
                                            ),
                                          ),
                                          const SizedBox(height: 8),
                                          Text(
                                            dayData['day'] ?? '',
                                            style: TextStyle(
                                              fontSize: 12,
                                              fontWeight: FontWeight.w500,
                                              color: Colors.grey.shade600,
                                            ),
                                          ),
                                        ],
                                      ),
                                    ),
                                  );
                                }).toList(),
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),

                  const SizedBox(height: 24),

                  // Recent Activity
                  Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 16),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text(
                          'Recent Activity',
                          style: TextStyle(
                            fontSize: 18,
                            fontWeight: FontWeight.bold,
                            color: Color(0xFF111418),
                          ),
                        ),
                        const SizedBox(height: 16),
                        if (attendanceRecords != null && attendanceRecords!.isNotEmpty)
                          ..._buildActivitySections(attendanceRecords!)
                        else
                          Padding(
                            padding: const EdgeInsets.all(16),
                            child: Center(
                              child: Text(
                                'No attendance records found',
                                style: TextStyle(
                                  color: Colors.grey.shade600,
                                  fontSize: 14,
                                ),
                              ),
                            ),
                          ),
                      ],
                    ),
                  ),

                  const SizedBox(height: 16),

                  // View Previous Months Button
                  Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 16),
                    child: SizedBox(
                      width: double.infinity,
                      child: TextButton(
                        onPressed: () {
                          // TODO: Navigate to previous months view
                        },
                        style: TextButton.styleFrom(
                          padding: const EdgeInsets.symmetric(vertical: 12),
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(8),
                          ),
                        ),
                        child: const Text(
                          'View Previous Months',
                          style: TextStyle(
                            color: Color(0xFF137FEC),
                            fontWeight: FontWeight.bold,
                            fontSize: 14,
                          ),
                        ),
                      ),
                    ),
                  ),

                  const SizedBox(height: 32),
                ],
              ),
            ),
    );
  }

  Widget _buildStatCard({
    required IconData icon,
    required Color iconColor,
    required String label,
    required String value,
    String? subtitle,
    Color? subtitleColor,
    double? progress,
    Color? progressColor,
  }) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: Colors.grey.shade200),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.05),
            blurRadius: 4,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                padding: const EdgeInsets.all(8),
                decoration: BoxDecoration(
                  color: iconColor.withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Icon(icon, color: iconColor, size: 18),
              ),
              const SizedBox(width: 8),
              Text(
                label.toUpperCase(),
                style: TextStyle(
                  fontSize: 11,
                  fontWeight: FontWeight.w600,
                  color: Colors.grey.shade600,
                  letterSpacing: 0.5,
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          Text(
            value,
            style: const TextStyle(
              fontSize: 28,
              fontWeight: FontWeight.bold,
              color: Color(0xFF111418),
            ),
          ),
          if (subtitle != null) ...[
            const SizedBox(height: 4),
            Text(
              subtitle,
              style: TextStyle(
                fontSize: 12,
                color: subtitleColor ?? Colors.grey.shade600,
                fontWeight: FontWeight.w500,
              ),
            ),
          ],
          if (progress != null) ...[
            const SizedBox(height: 8),
            Container(
              height: 6,
              decoration: BoxDecoration(
                color: Colors.grey.shade200,
                borderRadius: BorderRadius.circular(3),
              ),
              child: FractionallySizedBox(
                alignment: Alignment.centerLeft,
                widthFactor: progress,
                child: Container(
                  decoration: BoxDecoration(
                    color: progressColor ?? const Color(0xFF137FEC),
                    borderRadius: BorderRadius.circular(3),
                  ),
                ),
              ),
            ),
          ],
        ],
      ),
    );
  }

  Widget _buildToggleButton(String label, bool isSelected) {
    return GestureDetector(
      onTap: () {
        setState(() {
          _viewType = label;
        });
      },
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 8),
        decoration: BoxDecoration(
          color: isSelected ? Colors.white : Colors.transparent,
          borderRadius: BorderRadius.circular(6),
          boxShadow: isSelected
              ? [
                  BoxShadow(
                    color: Colors.black.withValues(alpha: 0.05),
                    blurRadius: 4,
                    offset: const Offset(0, 2),
                  ),
                ]
              : null,
        ),
        child: Text(
          label,
          textAlign: TextAlign.center,
          style: TextStyle(
            fontSize: 14,
            fontWeight: isSelected ? FontWeight.w600 : FontWeight.w500,
            color: isSelected ? const Color(0xFF111418) : Colors.grey.shade600,
          ),
        ),
      ),
    );
  }

  List<Widget> _buildActivitySections(List<dynamic> records) {
    final Map<String, List<Map<String, dynamic>>> grouped = {};
    
    for (var record in records) {
      if (record is Map<String, dynamic>) {
        final section = record['section'] ?? 'Earlier this week';
        if (!grouped.containsKey(section)) {
          grouped[section] = [];
        }
        grouped[section]!.add(record);
      }
    }

    // Maintain order: Today, Yesterday, Earlier this week
    final List<String> sectionOrder = ['Today', 'Yesterday', 'Earlier this week'];
    
    List<Widget> widgets = [];
    
    for (String section in sectionOrder) {
      if (grouped.containsKey(section)) {
        final activities = grouped[section]!;
        widgets.add(
          Padding(
            padding: const EdgeInsets.only(bottom: 16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Padding(
                  padding: const EdgeInsets.only(left: 4, bottom: 8),
                  child: Text(
                    section,
                    style: TextStyle(
                      fontSize: 14,
                      fontWeight: FontWeight.w600,
                      color: Colors.grey.shade600,
                    ),
                  ),
                ),
                ...activities.map((activity) => _buildActivityCard(activity)),
              ],
            ),
          ),
        );
      }
    }

    return widgets;
  }

  Widget _buildActivityCard(Map<String, dynamic> activity) {
    final borderColorMap = activity['border_color'] as Map<String, dynamic>?;
    final borderColor = _getColorFromMap(borderColorMap);
    final badge = activity['badge'] as String?;
    final badgeColorMap = activity['badge_color'] as Map<String, dynamic>?;
    final badgeColor = _getColorFromMap(badgeColorMap);
    final iconName = activity['icon'] as String?;
    final note = activity['note'] as String?;
    final section = activity['section'] as String?;
    final isYesterday = section == 'Yesterday';
    final isOldPresent = section == 'Earlier this week' && activity['status'] == 'Present' && iconName != null;

    // Get icon from string name
    IconData? icon;
    if (iconName == 'check_circle') {
      icon = Icons.check_circle;
    }

    return Opacity(
      opacity: (isYesterday || isOldPresent) ? 0.7 : 1.0,
      child: Container(
        margin: const EdgeInsets.only(bottom: 8),
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(12),
          border: Border(
            left: BorderSide(color: borderColor, width: 4),
            top: BorderSide(color: Colors.grey.shade200),
            right: BorderSide(color: Colors.grey.shade200),
            bottom: BorderSide(color: Colors.grey.shade200),
          ),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: 0.05),
              blurRadius: 4,
              offset: const Offset(0, 2),
            ),
          ],
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                // Date Box
                Container(
                  width: 40,
                  height: 40,
                  decoration: BoxDecoration(
                    color: Colors.grey.shade100,
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Text(
                        activity['date']?.toString() ?? '',
                        style: const TextStyle(
                          fontSize: 14,
                          fontWeight: FontWeight.bold,
                          color: Color(0xFF111418),
                        ),
                      ),
                      Text(
                        (activity['month']?.toString() ?? '').toUpperCase(),
                        style: TextStyle(
                          fontSize: 10,
                          color: Colors.grey.shade600,
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(width: 16),
                // Status and Time
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        activity['status']?.toString() ?? 'Unknown',
                        style: const TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.bold,
                          color: Color(0xFF111418),
                        ),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        activity['time']?.toString() ?? '',
                        style: TextStyle(
                          fontSize: 14,
                          color: badgeColorMap != null ? badgeColor : Colors.grey.shade600,
                          fontWeight: badgeColorMap != null ? FontWeight.w500 : FontWeight.normal,
                        ),
                      ),
                    ],
                  ),
                ),
                // Badge or Icon
                if (badge != null && badgeColorMap != null)
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                    decoration: BoxDecoration(
                      color: badgeColor.withValues(alpha: 0.1),
                      borderRadius: BorderRadius.circular(20),
                    ),
                    child: Text(
                      badge,
                      style: TextStyle(
                        fontSize: 12,
                        fontWeight: FontWeight.bold,
                        color: badgeColor,
                      ),
                    ),
                  )
                else if (icon != null)
                  Icon(icon, color: Colors.green, size: 24),
              ],
            ),
            // Note if present
            if (note != null && note.toString().isNotEmpty) ...[
              const SizedBox(height: 12),
              Padding(
                padding: const EdgeInsets.only(left: 56),
                child: Container(
                  padding: const EdgeInsets.all(8),
                  decoration: BoxDecoration(
                    color: Colors.grey.shade50,
                    borderRadius: BorderRadius.circular(6),
                  ),
                  child: Text(
                    note.toString(),
                    style: TextStyle(
                      fontSize: 12,
                      color: Colors.grey.shade700,
                    ),
                  ),
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }
}

