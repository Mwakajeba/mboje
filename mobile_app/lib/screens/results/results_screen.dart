import 'package:flutter/material.dart';
import 'package:fl_chart/fl_chart.dart';
import 'package:provider/provider.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../../services/parent_api_service.dart';
import '../../providers/language_provider.dart';

class ExamsResultsScreen extends StatefulWidget {
  const ExamsResultsScreen({super.key});

  @override
  State<ExamsResultsScreen> createState() => _ExamsResultsScreenState();
}

class _ExamsResultsScreenState extends State<ExamsResultsScreen> {
  String? selectedExamId;
  String? selectedStreamId; // 'all' or specific stream ID
  List<dynamic> exams = [];
  Map<String, dynamic>? examDetails;
  List<Map<String, dynamic>> allExamDetails = []; // Store all exam details for graph
  bool isLoadingExams = true;
  bool isLoadingDetails = false;
  int? studentId;
  Map<String, dynamic>? studentInfo; // Store student info for stream selection

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
        selectedStreamId = 'all'; // Default to all streams
      });
      _loadExams();
      _loadStudentInfo();
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
        
        // Load all exam details for graph
        _loadAllExamDetailsForGraph();
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

  Future<void> _loadAllExamDetailsForGraph() async {
    if (studentId == null || exams.isEmpty) return;
    
    try {
      List<Map<String, dynamic>> allDetails = [];
      
      for (var exam in exams) {
        final examTypeId = exam['exam_type_id']?.toString();
        final academicYearId = exam['academic_year_id']?.toString();
        
        if (examTypeId != null && academicYearId != null) {
          final examTypeIdInt = int.tryParse(examTypeId);
          final academicYearIdInt = int.tryParse(academicYearId);
          
          if (examTypeIdInt != null && academicYearIdInt != null) {
            final data = await ParentApiService.getExamDetails(studentId!, examTypeIdInt, academicYearIdInt);
            if (data != null) {
              allDetails.add(data);
            }
          }
        }
      }
      
      if (mounted) {
        setState(() {
          allExamDetails = allDetails;
        });
      }
    } catch (e) {
      print('Error loading all exam details: $e');
    }
  }

  Future<void> _loadStudentInfo() async {
    if (studentId == null) return;
    
    try {
      final data = await ParentApiService.getStudentDetails(studentId!);
      if (mounted && data != null) {
        setState(() {
          studentInfo = data;
        });
      }
    } catch (e) {
      print('Error loading student info: $e');
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
            
            /// STREAM SELECTOR (only show if exam is selected)
            if (selectedExamId != null && studentInfo != null)
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Chagua Mkondo:',
                    style: const TextStyle(
                      fontSize: 16,
                      color: Colors.white70,
                      fontWeight: FontWeight.w500,
                    ),
                  ),
                  const SizedBox(height: 10),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 15),
                    decoration: BoxDecoration(
                      color: Colors.white10,
                      borderRadius: BorderRadius.circular(10),
                    ),
                    child: DropdownButton<String>(
                      dropdownColor: Colors.black,
                      value: selectedStreamId,
                      hint: const Text(
                        'Chagua Mkondo',
                        style: TextStyle(color: Colors.white54),
                      ),
                      isExpanded: true,
                      underline: const SizedBox(),
                      items: [
                        const DropdownMenuItem<String>(
                          value: 'all',
                          child: Text(
                            'Mkondo Wote (All Streams)',
                            style: TextStyle(color: Colors.white),
                          ),
                        ),
                        if (studentInfo!['stream'] != null)
                          DropdownMenuItem<String>(
                            value: studentInfo!['stream']?['id']?.toString() ?? 'current',
                            child: Text(
                              '${studentInfo!['stream']?['name'] ?? 'Current Stream'}',
                              style: const TextStyle(color: Colors.white),
                            ),
                          ),
                      ],
                      onChanged: (value) {
                        setState(() {
                          selectedStreamId = value;
                          // Reload exam details with stream filter
                          if (selectedExamId != null) {
                            final parts = selectedExamId!.split('|');
                            final examTypeId = parts[0];
                            final academicYearId = parts[1];
                            final exam = exams.firstWhere(
                              (e) => e['exam_type_id']?.toString() == examTypeId &&
                                     e['academic_year_id']?.toString() == academicYearId,
                            );
                            _onExamSelected(exam);
                          }
                        });
                      },
                    ),
                  ),
                  const SizedBox(height: 20),
                ],
              ),
            
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
    }).map((s) => (s['subject_name'] ?? '').toString()).toList();
    
    final weakSubjects = subjects.where((s) {
      final grade = s['grade'] ?? '';
      return grade == 'D' || grade == 'E' || grade == 'F';
    }).map((s) => (s['subject_name'] ?? '').toString()).toList();

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
                        flex: 2,
                        child: Text(
                          'Grade',
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
                          'Position',
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
                  final classRank = subject['class_rank'] ?? '-';
                  
                  // Map grade to remark
                  String remark = _getRemarkFromGrade(grade);
                  
                  return subjectRow(
                    subject['subject_name'] ?? 'Unknown',
                    "${percentage.toStringAsFixed(0)}%",
                    grade,
                    classRank.toString(),
                    remark,
                    isFirst: isFirst,
                    isLast: isLast,
                  );
                }),
              ],
            ),
          ),
          const SizedBox(height: 15),

          /// OVERALL PERFORMANCE LEVELS SUMMARY
          Container(
            padding: const EdgeInsets.all(18),
            decoration: box(),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('Overall Performance Levels Summary', style: title()),
                const SizedBox(height: 15),
                _buildPerformanceSummaryTable(examData),
              ],
            ),
          ),
          const SizedBox(height: 15),

          /// GRAPH - Show performance over time across all exams
          Container(
            padding: const EdgeInsets.all(20),
            height: 220,
            decoration: box(),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('Maendeleo ya Mwanafunzi', style: title()),
                const SizedBox(height: 10),
                Expanded(
                  child: _buildProgressGraph(),
                ),
              ],
            ),
          ),
          const SizedBox(height: 15),

          /// AI ANALYSIS SECTION
          Container(
            padding: const EdgeInsets.all(18),
            decoration: box(),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    const Icon(Icons.psychology, color: Colors.purpleAccent, size: 24),
                    const SizedBox(width: 8),
                    Text('AI Analysis & Mapendekezo', style: title()),
                  ],
                ),
                const SizedBox(height: 15),
                _buildAIAnalysis(examData, subjects, strongSubjects, weakSubjects),
              ],
            ),
          ),
          const SizedBox(height: 15),
        ],
      ),
    );
  }

  Color _getGradeColor(String grade) {
    switch (grade.toUpperCase()) {
      case 'A':
        return Colors.greenAccent;
      case 'B':
        return Colors.blueAccent;
      case 'C':
        return Colors.orangeAccent;
      case 'D':
        return Colors.orange;
      case 'E':
      case 'F':
        return Colors.redAccent;
      case 'ABS':
        return Colors.grey;
      default:
        return Colors.white70;
    }
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

  /// Build Performance Summary Table
  Widget _buildPerformanceSummaryTable(Map<String, dynamic> examData) {
    // Get performance data from API
    final performanceData = examData['performance_by_gender'] ?? {
      'girls': {'A': 0, 'B': 0, 'C': 0, 'D': 0, 'total': 0},
      'boys': {'A': 0, 'B': 0, 'C': 0, 'D': 0, 'total': 0},
      'total': {'A': 0, 'B': 0, 'C': 0, 'D': 0, 'total': 0},
    };

    return Table(
      border: TableBorder.all(color: Colors.white24, width: 1),
      children: [
        // Header row
        TableRow(
          decoration: BoxDecoration(
            color: Colors.amber.withOpacity(0.2),
          ),
          children: [
            _buildTableCell('GENDER', isHeader: true),
            _buildTableCell('A', isHeader: true),
            _buildTableCell('B', isHeader: true),
            _buildTableCell('C', isHeader: true),
            _buildTableCell('D', isHeader: true),
            _buildTableCell('TOTAL', isHeader: true),
          ],
        ),
        // Girls row
        TableRow(
          children: [
            _buildTableCell('GIRLS'),
            _buildTableCell('${performanceData['girls']!['A']}'),
            _buildTableCell('${performanceData['girls']!['B']}'),
            _buildTableCell('${performanceData['girls']!['C']}'),
            _buildTableCell('${performanceData['girls']!['D']}'),
            _buildTableCell('${performanceData['girls']!['total']}'),
          ],
        ),
        // Boys row
        TableRow(
          children: [
            _buildTableCell('BOYS'),
            _buildTableCell('${performanceData['boys']!['A']}'),
            _buildTableCell('${performanceData['boys']!['B']}'),
            _buildTableCell('${performanceData['boys']!['C']}'),
            _buildTableCell('${performanceData['boys']!['D']}'),
            _buildTableCell('${performanceData['boys']!['total']}'),
          ],
        ),
        // Total row
        TableRow(
          decoration: BoxDecoration(
            color: Colors.white.withOpacity(0.05),
          ),
          children: [
            _buildTableCell('TOTAL', isBold: true),
            _buildTableCell('${performanceData['total']!['A']}', isBold: true),
            _buildTableCell('${performanceData['total']!['B']}', isBold: true),
            _buildTableCell('${performanceData['total']!['C']}', isBold: true),
            _buildTableCell('${performanceData['total']!['D']}', isBold: true),
            _buildTableCell('${performanceData['total']!['total']}', isBold: true),
          ],
        ),
      ],
    );
  }

  Widget _buildTableCell(String text, {bool isHeader = false, bool isBold = false}) {
    return Padding(
      padding: const EdgeInsets.all(8.0),
      child: Text(
        text,
        textAlign: TextAlign.center,
        style: TextStyle(
          color: Colors.white,
          fontSize: isHeader ? 12 : 11,
          fontWeight: isHeader || isBold ? FontWeight.bold : FontWeight.normal,
        ),
      ),
    );
  }

  /// Build Progress Graph
  Widget _buildProgressGraph() {
    if (allExamDetails.isEmpty) {
      // If we don't have all exam details yet, show current exam only
      final currentAverage = examDetails?['average'] ?? 0.0;
      return LineChart(
        LineChartData(
          titlesData: FlTitlesData(
            leftTitles: AxisTitles(
              sideTitles: SideTitles(
                showTitles: true,
                getTitlesWidget: (value, meta) {
                  return Text(
                    value.toInt().toString(),
                    style: const TextStyle(color: Colors.white54, fontSize: 10),
                  );
                },
              ),
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
                  if (examDetails != null) {
                    final examType = examDetails!['exam_type'] ?? 'Exam';
                    return Text(
                      examType.toString().substring(0, examType.toString().length > 8 ? 8 : examType.toString().length),
                      style: const TextStyle(color: Colors.white54, fontSize: 10),
                    );
                  }
                  return const Text('');
                },
              ),
            ),
          ),
          borderData: FlBorderData(show: true, border: Border.all(color: Colors.white24)),
          gridData: const FlGridData(show: true, drawVerticalLine: false),
          lineBarsData: [
            LineChartBarData(
              spots: [
                FlSpot(0, double.tryParse(currentAverage.toString()) ?? 0),
              ],
              isCurved: true,
              barWidth: 3,
              color: Colors.blueAccent,
              dotData: const FlDotData(show: true),
            )
          ],
        ),
      );
    }

    // Show progress across all exams
    final spots = allExamDetails.asMap().entries.map((entry) {
      final index = entry.key.toDouble();
      final exam = entry.value;
      final average = exam['average'] ?? 0.0;
      return FlSpot(index, double.tryParse(average.toString()) ?? 0);
    }).toList();

    final examLabels = allExamDetails.map((exam) {
      final examType = exam['exam_type'] ?? 'Exam';
      return examType.toString().substring(0, examType.toString().length > 8 ? 8 : examType.toString().length);
    }).toList();

    return LineChart(
      LineChartData(
        minY: 0,
        maxY: 100,
        titlesData: FlTitlesData(
          leftTitles: AxisTitles(
            sideTitles: SideTitles(
              showTitles: true,
              getTitlesWidget: (value, meta) {
                return Text(
                  value.toInt().toString(),
                  style: const TextStyle(color: Colors.white54, fontSize: 10),
                );
              },
            ),
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
                final index = v.toInt();
                if (index >= 0 && index < examLabels.length) {
                  return Text(
                    examLabels[index],
                    style: const TextStyle(color: Colors.white54, fontSize: 10),
                  );
                }
                return const Text('');
              },
            ),
          ),
        ),
        borderData: FlBorderData(show: true, border: Border.all(color: Colors.white24)),
        gridData: const FlGridData(show: true, drawVerticalLine: false),
        lineBarsData: [
          LineChartBarData(
            spots: spots,
            isCurved: true,
            barWidth: 3,
            color: Colors.blueAccent,
            dotData: const FlDotData(show: true),
            belowBarData: BarAreaData(show: true, color: Colors.blueAccent.withOpacity(0.1)),
          )
        ],
      ),
    );
  }

  /// Build AI Analysis Section
  Widget _buildAIAnalysis(
    Map<String, dynamic> examData,
    List<Map<String, dynamic>> subjects,
    List<dynamic> strongSubjects,
    List<dynamic> weakSubjects,
  ) {
    final average = examData['average'] ?? 0.0;
    final averageDouble = double.tryParse(average.toString()) ?? 0.0;
    final position = examData['position'] ?? '-';
    final grade = examData['grade'] ?? '-';
    
    // Generate AI recommendations based on performance
    List<String> recommendations = [];
    List<String> actions = [];
    
    if (averageDouble >= 80) {
      recommendations.add('🎉 Hongera! Mwanafunzi anaonyesha ufanisi mkuu.');
      recommendations.add('Endelea kuwapa msaada wa kudumisha hali hii bora.');
      actions.add('Zingatia masomo yaliyo bora zaidi kwa kuongeza changamoto.');
      actions.add('Wasaidie kujenga ujasiri zaidi katika masomo yanayoweza kuwa magumu.');
    } else if (averageDouble >= 70) {
      recommendations.add('Mwanafunzi anaonyesha ufanisi mzuri.');
      recommendations.add('Kuna nafasi ya kuboresha zaidi katika baadhi ya masomo.');
      actions.add('Zingatia masomo yaliyo chini ya wastani kwa msaada wa ziada.');
      actions.add('Endelea kuwapa moyo na msaada wa kudumisha ufanisi.');
    } else if (averageDouble >= 50) {
      recommendations.add('Mwanafunzi anaonyesha ufanisi wa wastani.');
      recommendations.add('Kuna haja ya msaada wa ziada ili kuboresha matokeo.');
      actions.add('Panga muda wa ziada wa kusoma kwa masomo yaliyo chini.');
      actions.add('Wasiliana na walimu kwa msaada wa ziada na maelekezo.');
      actions.add('Zingatia mazoezi ya ziada na kujifunza zaidi.');
    } else {
      recommendations.add('Mwanafunzi anaonyesha haja ya msaada wa haraka.');
      recommendations.add('Ni muhimu kuchukua hatua za haraka ili kuboresha matokeo.');
      actions.add('Wasiliana na walimu mara moja kwa msaada wa ziada.');
      actions.add('Panga muda wa ziada wa kusoma kila siku.');
      actions.add('Zingatia mazoezi ya ziada na masomo ya ziada.');
      actions.add('Tafuta msaada wa kitaaluma ikiwa ni lazima.');
    }

    // Subject-specific recommendations
    final strongSubjectsList = strongSubjects.map((s) => s.toString()).toList();
    final weakSubjectsList = weakSubjects.map((s) => s.toString()).toList();
    
    if (strongSubjectsList.isNotEmpty) {
      recommendations.add('Masomo yaliyo bora: ${strongSubjectsList.join(', ')} - Endelea kuwapa moyo katika masomo haya.');
    }
    
    if (weakSubjectsList.isNotEmpty) {
      recommendations.add('Masomo yanayohitaji msaada: ${weakSubjectsList.join(', ')} - Zingatia msaada wa ziada katika masomo haya.');
      actions.add('Panga muda wa ziada wa kusoma kwa masomo: ${weakSubjectsList.join(', ')}.');
    }

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        // Performance Summary
        Container(
          padding: const EdgeInsets.all(12),
          decoration: BoxDecoration(
            color: Colors.purple.withOpacity(0.2),
            borderRadius: BorderRadius.circular(8),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                'Muhtasari wa Utendaji:',
                style: const TextStyle(
                  color: Colors.purpleAccent,
                  fontSize: 14,
                  fontWeight: FontWeight.bold,
                ),
              ),
              const SizedBox(height: 8),
              Text(
                'Wastani: ${averageDouble.toStringAsFixed(1)}% | Nafasi: $position | Daraja: $grade',
                style: const TextStyle(color: Colors.white70, fontSize: 12),
              ),
            ],
          ),
        ),
        const SizedBox(height: 15),
        
        // Recommendations
        Text(
          'Mapendekezo:',
          style: const TextStyle(
            color: Colors.white,
            fontSize: 14,
            fontWeight: FontWeight.bold,
          ),
        ),
        const SizedBox(height: 8),
        ...recommendations.map((rec) => Padding(
          padding: const EdgeInsets.only(bottom: 8),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Icon(Icons.lightbulb_outline, color: Colors.amber, size: 16),
              const SizedBox(width: 8),
              Expanded(
                child: Text(
                  rec,
                  style: const TextStyle(color: Colors.white70, fontSize: 12),
                ),
              ),
            ],
          ),
        )),
        const SizedBox(height: 15),
        
        // Actions
        Text(
          'Hatua za Kuchukua:',
          style: const TextStyle(
            color: Colors.white,
            fontSize: 14,
            fontWeight: FontWeight.bold,
          ),
        ),
        const SizedBox(height: 8),
        ...actions.map((action) => Padding(
          padding: const EdgeInsets.only(bottom: 8),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Icon(Icons.check_circle_outline, color: Colors.greenAccent, size: 16),
              const SizedBox(width: 8),
              Expanded(
                child: Text(
                  action,
                  style: const TextStyle(color: Colors.white70, fontSize: 12),
                ),
              ),
            ],
          ),
        )),
      ],
    );
  }

  Widget subjectRow(String subject, String score, String grade, String position, String remark, {bool isFirst = false, bool isLast = false}) {
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
            flex: 2,
            child: Text(
              grade,
              textAlign: TextAlign.center,
              style: TextStyle(
                color: _getGradeColor(grade),
                fontSize: 14,
                fontWeight: FontWeight.bold,
              ),
            ),
          ),
          Expanded(
            flex: 2,
            child: Text(
              position,
              textAlign: TextAlign.center,
              style: const TextStyle(
                color: Colors.blueAccent,
                fontSize: 12,
                fontWeight: FontWeight.w600,
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

