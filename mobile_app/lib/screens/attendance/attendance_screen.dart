import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../../services/parent_api_service.dart';
import '../../providers/language_provider.dart';

class AttendancePage extends StatefulWidget {
  const AttendancePage({super.key});

  @override
  State<AttendancePage> createState() => _AttendancePageState();
}

class _AttendancePageState extends State<AttendancePage> {
  String _viewType = 'Weekly';

  Map<String, dynamic>? attendanceStats;
  List<dynamic> attendanceRecords = [];
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
    studentId = prefs.getInt('selected_student_id');

    if (studentId != null) {
      _loadAttendanceData();
    } else {
      setState(() => isLoading = false);
    }
  }

  Future<void> _loadAttendanceData() async {
    if (studentId == null) return;

    setState(() => isLoading = true);

    try {
      final results = await Future.wait([
        ParentApiService.getStudentAttendanceStats(studentId!),
        ParentApiService.getStudentAttendance(
          studentId!,
          limit: _viewType == 'Weekly' ? 7 : 30,
        ),
        ParentApiService.getStudentDetails(studentId!),
      ]);

      if (!mounted) return;

      setState(() {
        attendanceStats = results[0] as Map<String, dynamic>?;
        attendanceRecords = results[1] as List<dynamic>;
        studentInfo = results[2] as Map<String, dynamic>?;
        isLoading = false;
      });
    } catch (e) {
      debugPrint('Load error: $e');
      if (mounted) setState(() => isLoading = false);
    }
  }

  Color _getColorFromMap(Map<String, dynamic>? map) {
    if (map == null) return Colors.blue;
    return Color.fromRGBO(
      map['r'] ?? 0,
      map['g'] ?? 0,
      map['b'] ?? 255,
      1,
    );
  }

  @override
  Widget build(BuildContext context) {
    final language = Provider.of<LanguageProvider>(context);
    final tr = AppTranslations(language.currentLanguage);

    return Scaffold(
      backgroundColor: const Color(0xFFF6F7F8),
      appBar: AppBar(
        title: Text(tr.get('attendance_title')),
        centerTitle: true,
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: _loadAttendanceData,
          ),
        ],
      ),
      body: isLoading
          ? const Center(child: CircularProgressIndicator())
          : studentId == null
              ? const Center(child: Text('No student selected'))
              : SingleChildScrollView(
                  child: Column(
                    children: [
                      _studentCard(),
                      _statsRow(),
                      _toggle(),
                      _recentActivity(),
                    ],
                  ),
                ),
    );
  }

  Widget _studentCard() {
    return Padding(
      padding: const EdgeInsets.all(16),
      child: Card(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
        child: ListTile(
          leading: const CircleAvatar(child: Icon(Icons.person)),
          title: Text(
            studentInfo?['full_name'] ?? 'Student',
            style: const TextStyle(fontWeight: FontWeight.bold),
          ),
          subtitle: Text(
            '${studentInfo?['class']?['name'] ?? ''} '
            '${studentInfo?['stream']?['name'] ?? ''}',
          ),
        ),
      ),
    );
  }

  Widget _statsRow() {
    if (attendanceStats == null) return const SizedBox();

    final stats = attendanceStats!['stats'] ?? {};

    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16),
      child: Row(
        children: [
          _statCard('Rate', '${stats['rate'] ?? 0}%', Icons.percent),
          _statCard('Absences', '${stats['absences'] ?? 0}', Icons.event_busy),
          _statCard('Tardies', '${stats['tardies'] ?? 0}', Icons.schedule),
        ],
      ),
    );
  }

  Widget _statCard(String title, String value, IconData icon) {
    return Expanded(
      child: Card(
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            children: [
              Icon(icon, color: Colors.blue),
              const SizedBox(height: 8),
              Text(value,
                  style: const TextStyle(
                      fontSize: 20, fontWeight: FontWeight.bold)),
              Text(title,
                  style: const TextStyle(fontSize: 12, color: Colors.grey)),
            ],
          ),
        ),
      ),
    );
  }

  Widget _toggle() {
    return Padding(
      padding: const EdgeInsets.all(16),
      child: Row(
        children: [
          _toggleButton('Weekly'),
          _toggleButton('Monthly'),
        ],
      ),
    );
  }

  Widget _toggleButton(String label) {
    final selected = _viewType == label;

    return Expanded(
      child: GestureDetector(
        onTap: () {
          if (_viewType == label) return;
          setState(() => _viewType = label);
          _loadAttendanceData();
        },
        child: Container(
          padding: const EdgeInsets.all(10),
          decoration: BoxDecoration(
            color: selected ? Colors.white : Colors.transparent,
            borderRadius: BorderRadius.circular(8),
            boxShadow: selected
                ? [
                    BoxShadow(
                      color: Colors.black.withOpacity(0.05),
                      blurRadius: 4,
                    )
                  ]
                : null,
          ),
          child: Text(
            label,
            textAlign: TextAlign.center,
            style: TextStyle(
              fontWeight: selected ? FontWeight.bold : FontWeight.normal,
            ),
          ),
        ),
      ),
    );
  }

  Widget _recentActivity() {
    return Padding(
      padding: const EdgeInsets.all(16),
      child: attendanceRecords.isEmpty
          ? const Text('No attendance records')
          : Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: attendanceRecords
                  .map((e) => _activityCard(e))
                  .toList(),
            ),
    );
  }

  Widget _activityCard(Map<String, dynamic> item) {
    final borderColor = _getColorFromMap(item['border_color']);
    final note = item['note'] ?? item['notes'] ?? '';

    return Container(
      margin: const EdgeInsets.only(bottom: 8),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        border: Border(left: BorderSide(color: borderColor, width: 4)),
        borderRadius: BorderRadius.circular(12),
        boxShadow: [
          BoxShadow(color: Colors.black.withOpacity(0.05), blurRadius: 4)
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Column(
                children: [
                  Text(item['date'] ?? '',
                      style: const TextStyle(fontWeight: FontWeight.bold)),
                  Text(item['month'] ?? '',
                      style: const TextStyle(fontSize: 12)),
                ],
              ),
              const SizedBox(width: 16),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(item['status'] ?? '',
                        style: const TextStyle(fontWeight: FontWeight.bold)),
                    Text(item['time'] ?? '',
                        style: const TextStyle(color: Colors.grey)),
                  ],
                ),
              ),
            ],
          ),
          // Display teacher's note/comment if available
          if (note != null && note.toString().isNotEmpty) ...[
            const SizedBox(height: 12),
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: Colors.grey.shade50,
                borderRadius: BorderRadius.circular(8),
                border: Border.all(color: Colors.grey.shade200),
              ),
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Icon(Icons.note, size: 16, color: Colors.grey.shade600),
                  const SizedBox(width: 8),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'Maelezo ya Mwalimu:',
                          style: TextStyle(
                            fontSize: 11,
                            fontWeight: FontWeight.bold,
                            color: Colors.grey.shade700,
                          ),
                        ),
                        const SizedBox(height: 4),
                        Text(
                          note.toString(),
                          style: TextStyle(
                            fontSize: 12,
                            color: Colors.grey.shade800,
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
          ],
        ],
      ),
    );
  }
}

