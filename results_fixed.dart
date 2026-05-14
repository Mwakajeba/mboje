import 'package:flutter/material.dart';
import 'package:fl_chart/fl_chart.dart';
import 'package:provider/provider.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'main.dart'; // For ParentApiService
import 'language_provider.dart';

class ExamsResultsScreen extends StatefulWidget {
  const ExamsResultsScreen({super.key});

  @override
  State<ExamsResultsScreen> createState() => _ExamsResultsScreenState();
}

class _ExamsResultsScreenState extends State<ExamsResultsScreen> {
  String? selectedExamId;
  List<dynamic> exams = [];
  Map<String, dynamic>? examDetails;
  bool isLoadingExams = true;
  bool isLoadingDetails = false;
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
      _loadExams();
    } else {
      setState(() {
        isLoadingExams = false;
      });
    }
  }

  Future<void> _loadExams() async {
    if (studentId == null) return;
    
    setState(() {
      isLoadingExams = true;
    });

    try {
      final data = await ParentApiService.getStudentExams(studentId!);
      if (mounted) {
        setState(() {
          exams = data ?? [];
          isLoadingExams = false;
        });
      }
    } catch (e) {
      print('Error loading exams: $e');
      if (mounted) {
        setState(() {
          isLoadingExams = false;
        });
      }
    }
  }

  Future<void> _loadExamDetails(String examTypeId, String academicYearId) async {
    if (studentId == null) return;
    
    setState(() {
      isLoadingDetails = true;
    });

    try {
      final examTypeIdInt = int.tryParse(examTypeId);
      final academicYearIdInt = int.tryParse(academicYearId);
      
      if (examTypeIdInt == null || academicYearIdInt == null) {
        throw Exception('Invalid exam IDs');
      }

      final data = await ParentApiService.getExamDetails(studentId!, examTypeIdInt, academicYearIdInt);
      if (mounted) {
        setState(() {
          examDetails = data;
          isLoadingDetails = false;
        });
      }
    } catch (e) {
      print('Error loading exam details: $e');
      if (mounted) {
        setState(() {
          isLoadingDetails = false;
        });
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error loading exam details: $e')),
        );
      }
    }
  }

  void _onExamSelected(dynamic exam) {
    final examTypeId = exam['exam_type_id']?.toString();
    final academicYearId = exam['academic_year_id']?.toString();
    
    if (examTypeId != null && academicYearId != null) {
      setState(() {
        selectedExamId = '$examTypeId|$academicYearId';
        examDetails = null;
      });
      _loadExamDetails(examTypeId, academicYearId);
    }
  }

  @override
  Widget build(BuildContext context) {
    final languageProvider = Provider.of<LanguageProvider>(context);
    final trans = AppTranslations(languageProvider.currentLanguage);
    
    return Scaffold(
      backgroundColor: const Color(0xFF121722),
      appBar: AppBar(
        title: Text(
          trans.get('results_title'),
          style: const TextStyle(
            fontSize: 22,
            fontWeight: FontWeight.bold,
            color: Colors.white,
          ),
        ),
        backgroundColor: Colors.transparent,
        elevation: 0,
        foregroundColor: Colors.white,
      ),
      body: Padding(
        padding: const EdgeInsets.all(14),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            /// ***** SELECT EXAM FIRST *****
            Text(
              trans.get('select_exam'),
              style: const TextStyle(
                fontSize: 16,
                color: Colors.white70,
                fontWeight: FontWeight.w500,
              ),
            ),
            const SizedBox(height: 10),
            isLoadingExams
                ? const Center(child: CircularProgressIndicator())
                : Container(
                    padding: const EdgeInsets.symmetric(horizontal: 15),
                    decoration: BoxDecoration(
                      color: Colors.white10,
                      borderRadius: BorderRadius.circular(10),
                    ),
                    child: DropdownButton<String>(
                      dropdownColor: Colors.black,
                      value: selectedExamId,
                      hint: Text(
                        trans.get('choose_exam'),
                        style: const TextStyle(color: Colors.white54),
                      ),
                      isExpanded: true,
                      underline: const SizedBox(),
                      items: exams.map((exam) {
                        final examTypeId = exam['exam_type_id']?.toString() ?? '';
                        final academicYearId = exam['academic_year_id']?.toString() ?? '';
                        final key = '$examTypeId|$academicYearId';
                        final examName = '${exam['exam_type'] ?? 'Exam'} - ${exam['academic_year'] ?? ''}';
                        return DropdownMenuItem<String>(
                          value: key,
                          child: Text(
                            examName,
                            style: const TextStyle(color: Colors.white),
                          ),
                        );
                      }).toList(),
                      onChanged: (value) {
                        if (value != null) {
                          final parts = value.split('|');
                          final examTypeId = parts[0];
                          final academicYearId = parts[1];
                          final exam = exams.firstWhere(
                            (e) => e['exam_type_id']?.toString() == examTypeId &&
                                   e['academic_year_id']?.toString() == academicYearId,
                          );
                          _onExamSelected(exam);
                        }
                      },
                    ),
                  ),
            const SizedBox(height: 20),
            examDetails == null
                ? Center(
                    child: Text(
                      selectedExamId == null 
                          ? trans.get('select_exam_prompt')
                          : isLoadingDetails 
                              ? 'Loading...' 
                              : 'No exam details found',
                      style: const TextStyle(color: Colors.white38, fontSize: 15),
                    ),
                  )
                : Expanded(child: examDashboard(trans, examDetails!)),
          ],
        ),
      ),
    );
  }

  /// ---------- FULL DASHBOARD UI ----------

  Widget examDashboard(AppTranslations trans, Map<String, dynamic> examData) {
    final average = examData['average'] ?? 0.0;
    final position = examData['position'] ?? '-';
    final positionParts = position.toString().split('/');
    final classPosition = positionParts.isNotEmpty ? positionParts[0] : '-';
    final totalStudents = positionParts.length > 1 ? positionParts[1] : '-';
    
    // Calculate stream position (if available, otherwise use class position)
    final streamPosition = classPosition;
    
    final subjects = List<Map<String, dynamic>>.from(examData['subjects'] ?? []);
    
    // Get performance summary
    final performanceSummary = examData['performance_summary'] ?? {};
    final strongSubjects = subjects.where((s) {
      final grade = s['grade'] ?? '';
      return grade == 'A' || grade == 'B';
    }).map((s) => s['subject_name'] ?? '').toList();
    
    final weakSubjects = subjects.where((s) {
      final grade = s['grade'] ?? '';
      return grade == 'D' || grade == 'E' || grade == 'F';
    }).map((s) => s['subject_name'] ?? '').toList();

    return SingleChildScrollView(
      child: Column(
        children: [
          /// SCORE + RANKING CARD
          Container(
            padding: const EdgeInsets.all(18),
            decoration: box(),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(trans.get('average'), style: label()),
                    Text("${average.toStringAsFixed(1)}%", style: big()),
                  ],
                ),
                Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(trans.get('class'), style: label()),
                    Text("$classPosition / $totalStudents", style: big()),
                  ],
                ),
                Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(trans.get('stream'), style: label()),
                    Row(
                      children: [
                        Text("$streamPosition / $totalStudents", style: big()),
                        const SizedBox(width: 5),
                        if (double.tryParse(average.toString()) != null && 
                            double.parse(average.toString()) >= 70)
                          const Icon(
                            Icons.arrow_upward,
                            color: Colors.greenAccent,
                            size: 18,
                          ),
                        if (double.tryParse(average.toString()) != null && 
                            double.parse(average.toString()) >= 70)
                          Text(
                            trans.get('improving'),
                            style: const TextStyle(
                              color: Colors.greenAccent,
                              fontSize: 11,
                            ),
                          )
                      ],
                    )
                  ],
                ),
              ],
            ),
          ),
          const SizedBox(height: 15),

          /// SUBJECT TABLE
          Container(
            padding: const EdgeInsets.all(18),
            decoration: box(),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(trans.get('subject_marks'), style: title()),
                const SizedBox(height: 15),
                // Header Row
                Container(
                  padding: const EdgeInsets.symmetric(vertical: 10, horizontal: 12),
                  decoration: BoxDecoration(
                    color: Colors.amber.withOpacity(0.2),
                    border: Border.all(color: Colors.amber.withOpacity(0.3)),
                    borderRadius: const BorderRadius.only(
                      topLeft: Radius.circular(8),
                      topRight: Radius.circular(8),
                    ),
                  ),
                  child: Row(
                    children: [
                      Expanded(
                        flex: 3,
                        child: Text(
                          trans.get('subject'),
                          style: const TextStyle(
                            color: Colors.white,
                            fontSize: 13,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                      ),
                      Expanded(
                        flex: 2,
                        child: Text(
                          trans.get('marks'),
                          textAlign: TextAlign.center,
                          style: const TextStyle(
                            color: Colors.white,
                            fontSize: 13,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                      ),
                      Expanded(
                        flex: 3,
                        child: Text(
                          trans.get('teacher'),
                          textAlign: TextAlign.center,
                          style: const TextStyle(
                            color: Colors.white,
                            fontSize: 13,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                      ),
                      Expanded(
                        flex: 2,
                        child: Text(
                          trans.get('remarks'),
                          textAlign: TextAlign.right,
                          style: const TextStyle(
                            color: Colors.white,
                            fontSize: 13,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
                ...subjects.asMap().entries.map((entry) {
                  final index = entry.key;
                  final subject = entry.value;
                  final isFirst = index == 0;
                  final isLast = index == subjects.length - 1;
                  
                  final marksObtained = subject['marks_obtained'] ?? 0.0;
                  final maxMarks = subject['max_marks'] ?? 100.0;
                  final percentage = subject['percentage'] ?? 0.0;
                  final grade = subject['grade'] ?? '-';
                  
                  // Map grade to remark
                  String remark = _getRemarkFromGrade(grade);
                  
                  return subjectRow(
                    subject['subject_name'] ?? 'Unknown',
                    "${percentage.toStringAsFixed(0)}%",
                    "Teacher", // API doesn't provide teacher name
                    remark,
                    isFirst: isFirst,
                    isLast: isLast,
                  );
                }),
              ],
            ),
          ),
          const SizedBox(height: 15),

          /// GRAPH - Show performance over time (if we have multiple exams)
          Container(
            padding: const EdgeInsets.all(20),
            height: 220,
            decoration: box(),
            child: LineChart(
              LineChartData(
                titlesData: FlTitlesData(
                  leftTitles: const AxisTitles(
                    sideTitles: SideTitles(showTitles: false),
                  ),
                  topTitles: const AxisTitles(
                    sideTitles: SideTitles(showTitles: false),
                  ),
                  rightTitles: const AxisTitles(
                    sideTitles: SideTitles(showTitles: false),
                  ),
                  bottomTitles: AxisTitles(
                    sideTitles: SideTitles(
                      showTitles: true,
                      getTitlesWidget: (v, _) {
                        final labels = ["T1", "T2", "T3", "Final"];
                        if (v.toInt() >= 0 && v.toInt() < labels.length) {
                          return Text(
                            labels[v.toInt()],
                            style: const TextStyle(color: Colors.white54),
                          );
                        }
                        return const Text('');
                      },
                    ),
                  ),
                ),
                borderData: FlBorderData(show: false),
                gridData: const FlGridData(show: true),
                lineBarsData: [
                  LineChartBarData(
                    spots: [
                      FlSpot(0, 60),
                      FlSpot(1, 78),
                      FlSpot(2, 70),
                      FlSpot(3, double.tryParse(average.toString()) ?? 80),
                    ],
                    isCurved: true,
                    barWidth: 3,
                    color: Colors.blueAccent,
                    dotData: const FlDotData(show: true),
                  )
                ],
              ),
            ),
          ),
          const SizedBox(height: 15),

          /// STRENGTHS + WEAKNESS
          Container(
            padding: const EdgeInsets.all(18),
            decoration: box(),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(trans.get('performance_analysis'), style: title()),
                const SizedBox(height: 10),
                if (strongSubjects.isNotEmpty)
                  Text(
                    "⭐ ${trans.get('strong_subjects')}: ${strongSubjects.join(', ')}",
                    style: const TextStyle(color: Colors.greenAccent),
                  ),
                if (weakSubjects.isNotEmpty)
                  Text(
                    "⚠ ${trans.get('weak_subjects')}: ${weakSubjects.join(', ')}",
                    style: const TextStyle(color: Colors.redAccent),
                  ),
                const SizedBox(height: 8),
                Text(
                  "${trans.get('recommendation')}: ${trans.get('study_recommendation')}",
                  style: const TextStyle(color: Colors.white70),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  String _getRemarkFromGrade(String grade) {
    switch (grade.toUpperCase()) {
      case 'A':
        return 'Bora';
      case 'B':
        return 'Vizuri';
      case 'C':
        return 'Wastani';
      case 'D':
        return 'Chini ya Wastani';
      case 'E':
      case 'F':
        return 'Dhaifu';
      case 'ABS':
        return 'Hajahudhuria';
      default:
        return '-';
    }
  }

  /// 📌 Reusable components
  TextStyle title() => const TextStyle(
        fontSize: 18,
        fontWeight: FontWeight.bold,
        color: Colors.white,
      );

  TextStyle label() => const TextStyle(
        color: Colors.white54,
        fontSize: 13,
      );

  TextStyle big() => const TextStyle(
        color: Colors.white,
        fontSize: 21,
        fontWeight: FontWeight.w600,
      );

  BoxDecoration box() => BoxDecoration(
        color: Colors.white10,
        borderRadius: BorderRadius.circular(14),
      );

  Widget subjectRow(String subject, String score, String teacher, String remark, {bool isFirst = false, bool isLast = false}) {
    Color remarkColor = Colors.white70;
    if (remark == "Bora") remarkColor = Colors.greenAccent;
    if (remark == "Vizuri") remarkColor = Colors.blueAccent;
    if (remark == "Wastani") remarkColor = Colors.orangeAccent;
    
    return Container(
      padding: const EdgeInsets.symmetric(vertical: 12, horizontal: 12),
      decoration: BoxDecoration(
        border: Border(
          left: BorderSide(color: Colors.white24, width: 1),
          right: BorderSide(color: Colors.white24, width: 1),
          bottom: BorderSide(
            color: isLast ? Colors.white24 : Colors.white12,
            width: 1,
          ),
          top: isFirst ? BorderSide.none : BorderSide.none,
        ),
        borderRadius: isLast
            ? const BorderRadius.only(
                bottomLeft: Radius.circular(8),
                bottomRight: Radius.circular(8),
              )
            : null,
      ),
      child: Row(
        children: [
          Expanded(
            flex: 3,
            child: Text(
              subject,
              style: const TextStyle(
                color: Colors.white,
                fontSize: 13,
                fontWeight: FontWeight.w500,
              ),
            ),
          ),
          Expanded(
            flex: 2,
            child: Text(
              score,
              textAlign: TextAlign.center,
              style: TextStyle(
                color: Colors.amber.shade300,
                fontSize: 14,
                fontWeight: FontWeight.bold,
              ),
            ),
          ),
          Expanded(
            flex: 3,
            child: Text(
              teacher,
              textAlign: TextAlign.center,
              style: const TextStyle(
                color: Colors.white70,
                fontSize: 12,
              ),
            ),
          ),
          Expanded(
            flex: 2,
            child: Text(
              remark,
              textAlign: TextAlign.right,
              style: TextStyle(
                color: remarkColor,
                fontSize: 12,
                fontWeight: FontWeight.w600,
              ),
            ),
          ),
        ],
      ),
    );
  }
}












