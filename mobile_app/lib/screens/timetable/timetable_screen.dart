import 'package:flutter/material.dart';
import '../../services/parent_api_service.dart';

class TimetablePage extends StatefulWidget {
  final int studentId;
  const TimetablePage({super.key, required this.studentId});

  @override
  State<TimetablePage> createState() => _TimetablePageState();
}

class _TimetablePageState extends State<TimetablePage> {
  Map<String, dynamic>? timetableData;
  bool isLoading = true;
  late String selectedDay;

  @override
  void initState() {
    super.initState();
    // Set default day to today
    final now = DateTime.now();
    final days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
    selectedDay = days[now.weekday - 1]; // weekday is 1-7, Monday is 1
    _loadTimetable();
  }

  Future<void> _loadTimetable() async {
    setState(() {
      isLoading = true;
    });

    try {
      final data = await ParentApiService.getTimetable(widget.studentId);
      
      if (mounted) {
        setState(() {
          timetableData = data;
          isLoading = false;
        });
      }
    } catch (e) {
      print('Error loading timetable: $e');
      if (mounted) {
        setState(() {
          isLoading = false;
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.grey.shade50,
      appBar: AppBar(
        title: const Text(
          'Ratiba ya Masomo',
          style: TextStyle(
            fontWeight: FontWeight.bold,
            fontSize: 22,
          ),
        ),
        backgroundColor: Colors.indigo,
        foregroundColor: Colors.white,
        elevation: 0,
      ),
      body: isLoading
          ? const Center(child: CircularProgressIndicator())
          : timetableData == null || timetableData!.isEmpty
              ? Center(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Icon(Icons.schedule_outlined, size: 64, color: Colors.grey.shade400),
                      const SizedBox(height: 16),
                      Text(
                        'Hakuna ratiba iliyopatikana',
                        style: TextStyle(
                          fontSize: 16,
                          color: Colors.grey.shade600,
                        ),
                      ),
                    ],
                  ),
                )
              : Column(
                  children: [
                    // Timetable Info Header
                    Container(
                      padding: const EdgeInsets.all(16),
                      decoration: BoxDecoration(
                        gradient: LinearGradient(
                          colors: [Colors.indigo.shade700, Colors.indigo.shade500],
                        ),
                      ),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            timetableData!['timetable_name'] ?? 'Ratiba',
                            style: const TextStyle(
                              color: Colors.white,
                              fontSize: 20,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                          const SizedBox(height: 4),
                          Text(
                            'Ratiba ya Masomo',
                            style: TextStyle(
                              color: Colors.white.withOpacity(0.9),
                              fontSize: 14,
                            ),
                          ),
                        ],
                      ),
                    ),
                    // Day Selector
                    Container(
                      height: 60,
                      padding: const EdgeInsets.symmetric(vertical: 8),
                      child: ListView(
                        scrollDirection: Axis.horizontal,
                        padding: const EdgeInsets.symmetric(horizontal: 8),
                        children: ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday']
                            .map((day) => _buildDaySelector(day))
                            .toList(),
                      ),
                    ),
                    // Timetable Content
                    Expanded(
                      child: _buildDayTimetable(),
                    ),
                  ],
                ),
    );
  }

  Widget _buildDaySelector(String day) {
    final isSelected = selectedDay == day;
    final dayNames = {
      'Monday': 'Jumatatu',
      'Tuesday': 'Jumanne',
      'Wednesday': 'Jumatano',
      'Thursday': 'Alhamisi',
      'Friday': 'Ijumaa',
      'Saturday': 'Jumamosi',
      'Sunday': 'Jumapili',
    };

    return GestureDetector(
      onTap: () {
        setState(() {
          selectedDay = day;
        });
      },
      child: Container(
        margin: const EdgeInsets.symmetric(horizontal: 4),
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
        decoration: BoxDecoration(
          color: isSelected ? Colors.indigo : Colors.white,
          borderRadius: BorderRadius.circular(20),
          border: Border.all(
            color: isSelected ? Colors.indigo : Colors.grey.shade300,
            width: 2,
          ),
        ),
        child: Center(
          child: Text(
            dayNames[day] ?? day,
            style: TextStyle(
              color: isSelected ? Colors.white : Colors.grey.shade700,
              fontWeight: isSelected ? FontWeight.bold : FontWeight.normal,
              fontSize: 13,
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildDayTimetable() {
    // timetableData is already a Map of days
    final days = timetableData as Map<String, dynamic>?;
    if (days == null || days.isEmpty || !days.containsKey(selectedDay)) {
      return Center(
        child: Text(
          'Hakuna masomo kwa siku hii',
          style: TextStyle(
            fontSize: 16,
            color: Colors.grey.shade600,
          ),
        ),
      );
    }

    final dayEntries = days[selectedDay] as List<dynamic>?;
    if (dayEntries == null || dayEntries.isEmpty) {
      return Center(
        child: Text(
          'Hakuna masomo kwa siku hii',
          style: TextStyle(
            fontSize: 16,
            color: Colors.grey.shade600,
          ),
        ),
      );
    }

    return ListView(
      padding: const EdgeInsets.all(16),
      children: dayEntries.map<Widget>((entry) {
        return _buildPeriodCard(entry);
      }).toList(),
    );
  }

  Widget _buildPeriodCard(Map<String, dynamic> entry) {
    final periodName = entry['period_name'] ?? 'Period ${entry['period_number']}';
    final startTime = entry['start_time'] ?? '';
    final endTime = entry['end_time'] ?? '';
    final subject = entry['subject'] as Map<String, dynamic>?;
    final teacher = entry['teacher'] as Map<String, dynamic>?;
    final room = entry['room'] as Map<String, dynamic>?;
    final isDoublePeriod = entry['is_double_period'] ?? false;
    final isPractical = entry['is_practical'] ?? false;

    // Get subject name
    final subjectName = subject?['name'] ?? subject?['short_name'] ?? 'N/A';
    final subjectShortName = subject?['short_name'] ?? subjectName;

    return Container(
      margin: const EdgeInsets.only(bottom: 16),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: [Colors.white, Colors.indigo.shade50],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
          color: Colors.indigo.shade200,
          width: 1.5,
        ),
        boxShadow: [
          BoxShadow(
            color: Colors.indigo.withOpacity(0.1),
            blurRadius: 10,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Time Column - Enhanced
            Container(
              width: 75,
              padding: const EdgeInsets.all(10),
              decoration: BoxDecoration(
                gradient: LinearGradient(
                  colors: [Colors.indigo.shade600, Colors.indigo.shade400],
                  begin: Alignment.topCenter,
                  end: Alignment.bottomCenter,
                ),
                borderRadius: BorderRadius.circular(12),
                boxShadow: [
                  BoxShadow(
                    color: Colors.indigo.withOpacity(0.3),
                    blurRadius: 5,
                    offset: const Offset(0, 2),
                  ),
                ],
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.center,
                mainAxisSize: MainAxisSize.min,
                children: [
                  if (startTime != null && startTime.toString().isNotEmpty)
                    Text(
                      _formatTime(startTime.toString()),
                      style: const TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.bold,
                        color: Colors.white,
                      ),
                    ),
                  if (endTime != null && endTime.toString().isNotEmpty) ...[
                    const SizedBox(height: 2),
                    Text(
                      _formatTime(endTime.toString()),
                      style: TextStyle(
                        fontSize: 12,
                        color: Colors.white.withOpacity(0.9),
                      ),
                    ),
                  ],
                ],
              ),
            ),
            const SizedBox(width: 16),
            // Subject Info - Enhanced
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Subject Name with Badges
                  Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              subjectName,
                              style: const TextStyle(
                                fontSize: 18,
                                fontWeight: FontWeight.bold,
                                color: Colors.black87,
                                letterSpacing: 0.3,
                              ),
                            ),
                            if (subjectShortName != subjectName && subjectShortName != 'N/A')
                              Padding(
                                padding: const EdgeInsets.only(top: 2),
                                child: Text(
                                  subjectShortName,
                                  style: TextStyle(
                                    fontSize: 12,
                                    color: Colors.grey.shade600,
                                    fontStyle: FontStyle.italic,
                                  ),
                                ),
                              ),
                          ],
                        ),
                      ),
                      const SizedBox(width: 8),
                      // Badges
                      Wrap(
                        spacing: 6,
                        runSpacing: 6,
                        children: [
                          if (isDoublePeriod)
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                              decoration: BoxDecoration(
                                gradient: LinearGradient(
                                  colors: [Colors.blue.shade400, Colors.blue.shade600],
                                ),
                                borderRadius: BorderRadius.circular(12),
                                boxShadow: [
                                  BoxShadow(
                                    color: Colors.blue.withOpacity(0.3),
                                    blurRadius: 3,
                                    offset: const Offset(0, 1),
                                  ),
                                ],
                              ),
                              child: Text(
                                '2 Periods',
                                style: TextStyle(
                                  fontSize: 9,
                                  color: Colors.white,
                                  fontWeight: FontWeight.bold,
                                ),
                              ),
                            ),
                          if (isPractical)
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                              decoration: BoxDecoration(
                                gradient: LinearGradient(
                                  colors: [Colors.green.shade400, Colors.green.shade600],
                                ),
                                borderRadius: BorderRadius.circular(12),
                                boxShadow: [
                                  BoxShadow(
                                    color: Colors.green.withOpacity(0.3),
                                    blurRadius: 3,
                                    offset: const Offset(0, 1),
                                  ),
                                ],
                              ),
                              child: Text(
                                'Practical',
                                style: TextStyle(
                                  fontSize: 9,
                                  color: Colors.white,
                                  fontWeight: FontWeight.bold,
                                ),
                              ),
                            ),
                        ],
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),
                  // Teacher Info
                  if (teacher != null)
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                      decoration: BoxDecoration(
                        color: Colors.blue.shade50,
                        borderRadius: BorderRadius.circular(10),
                        border: Border.all(color: Colors.blue.shade100),
                      ),
                      child: Row(
                        children: [
                          Icon(Icons.person_outline, size: 16, color: Colors.blue.shade700),
                          const SizedBox(width: 8),
                          Expanded(
                            child: Text(
                              'Mwalimu: ${teacher['name'] ?? teacher['full_name'] ?? 'N/A'}',
                              style: TextStyle(
                                fontSize: 13,
                                color: Colors.blue.shade900,
                                fontWeight: FontWeight.w600,
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                  const SizedBox(height: 8),
                  // Room Info
                  if (room != null)
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                      decoration: BoxDecoration(
                        color: Colors.purple.shade50,
                        borderRadius: BorderRadius.circular(10),
                        border: Border.all(color: Colors.purple.shade100),
                      ),
                      child: Row(
                        children: [
                          Icon(Icons.room_outlined, size: 16, color: Colors.purple.shade700),
                          const SizedBox(width: 8),
                          Expanded(
                            child: Text(
                              'Chumba: ${room['name'] ?? 'N/A'}',
                              style: TextStyle(
                                fontSize: 13,
                                color: Colors.purple.shade900,
                                fontWeight: FontWeight.w600,
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  String _formatTime(String time) {
    if (time.isEmpty) return '';
    try {
      // Handle different time formats (HH:mm:ss or HH:mm)
      final parts = time.split(':');
      if (parts.length >= 2) {
        return '${parts[0]}:${parts[1]}';
      }
      return time;
    } catch (e) {
      return time;
    }
  }
}

