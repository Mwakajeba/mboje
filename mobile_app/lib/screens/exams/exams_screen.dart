import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../services/parent_api_service.dart';
import '../results/results_screen.dart';
import '../assignments/assignment_detail_screen.dart';

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
  Map<String, dynamic>? assignmentsData;
  List<dynamic> libraryMaterials = [];
  bool isLoadingSubjects = true;
  bool isLoadingExams = true;
  bool isLoadingAssignments = true;
  bool isLoadingMaterials = true;

  @override
  void initState() {
    _tabController = TabController(length: 4, vsync: this);
    super.initState();
    _loadSubjects();
    _loadExams();
    _loadAssignments();
    _loadLibraryMaterials();
  }
  
  Future<void> _loadLibraryMaterials() async {
    setState(() {
      isLoadingMaterials = true;
    });
    
    try {
      final data = await ParentApiService.getLibraryMaterials(widget.studentId);
      if (mounted) {
        setState(() {
          libraryMaterials = data ?? [];
          isLoadingMaterials = false;
        });
      }
    } catch (e) {
      print('Error loading library materials: $e');
      if (mounted) {
        setState(() {
          isLoadingMaterials = false;
        });
      }
    }
  }
  
  Future<void> _loadAssignments() async {
    setState(() {
      isLoadingAssignments = true;
    });
    
    try {
      final data = await ParentApiService.getStudentAssignments(widget.studentId);
      if (mounted) {
        setState(() {
          assignmentsData = data;
          isLoadingAssignments = false;
        });
      }
    } catch (e) {
      print('Error loading assignments: $e');
      if (mounted) {
        setState(() {
          isLoadingAssignments = false;
        });
      }
    }
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
          // ===== MENU 1: Subjects ===== //
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
                          // Header Section
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
                          // Subjects Grid
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
                              
                              // Define color pairs
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

          // ===== MENU 2: Exams ===== //
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
                          // Header Section
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
                                    Icons.assignment,
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
                                        'Matokeo ya Mitihani',
                                        style: TextStyle(
                                          color: Colors.white,
                                          fontSize: 20,
                                          fontWeight: FontWeight.bold,
                                        ),
                                      ),
                                      const SizedBox(height: 4),
                                      Text(
                                        '${exams.length} ${exams.length == 1 ? 'Mtihani' : 'Mitihani'}',
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
                          // Summary Cards
                          Row(
                            children: [
                              Expanded(
                                child: _buildSummaryCard(
                                  'Jumla ya Mitihani',
                                  '${exams.length}',
                                  Icons.assignment,
                                  Colors.blue,
                                ),
                              ),
                              const SizedBox(width: 12),
                              Expanded(
                                child: _buildSummaryCard(
                                  'Wastani wa Jumla',
                                  _calculateOverallAverage(exams),
                                  Icons.trending_up,
                                  Colors.green,
                                ),
                              ),
                            ],
                          ),
                          const SizedBox(height: 20),
                        
                        // Exam Cards
                        ...exams.map<Widget>((exam) {
                          return _buildExamCard(context, exam, widget.studentId);
                        }),
                        
                        const SizedBox(height: 20),
                        
                        // Academic Progress Section
                        Container(
                          padding: const EdgeInsets.all(20),
                          decoration: BoxDecoration(
                            gradient: LinearGradient(
                              colors: [Colors.purple.shade50, Colors.blue.shade50],
                              begin: Alignment.topLeft,
                              end: Alignment.bottomRight,
                            ),
                            borderRadius: BorderRadius.circular(16),
                            border: Border.all(
                              color: Colors.purple.shade100,
                              width: 1,
                            ),
                            boxShadow: [
                              BoxShadow(
                                color: Colors.purple.withOpacity(0.1),
                                blurRadius: 10,
                                offset: const Offset(0, 4),
                              ),
                            ],
                          ),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Row(
                                children: [
                                  Container(
                                    padding: const EdgeInsets.all(10),
                                    decoration: BoxDecoration(
                                      gradient: LinearGradient(
                                        colors: [Colors.purple.shade400, Colors.purple.shade600],
                                      ),
                                      borderRadius: BorderRadius.circular(12),
                                    ),
                                    child: const Icon(
                                      Icons.trending_up,
                                      color: Colors.white,
                                      size: 24,
                                    ),
                                  ),
                                  const SizedBox(width: 12),
                                  const Expanded(
                                    child: Text(
                                      'Maendeleo ya Kimaalum',
                                      style: TextStyle(
                                        fontSize: 20,
                                        fontWeight: FontWeight.bold,
                                        color: Colors.black87,
                                      ),
                                    ),
                                  ),
                                ],
                              ),
                              const SizedBox(height: 20),
                              ...exams.map<Widget>((exam) {
                                double averagePercent = 0.0;
                                
                                if (exam['average_percentage'] != null) {
                                  final avg = exam['average_percentage'];
                                  if (avg is num) {
                                    averagePercent = avg.toDouble();
                                  }
                                } else if (exam['average'] != null) {
                                  final avg = exam['average'];
                                  if (avg is num) {
                                    final avgValue = avg.toDouble();
                                    if (avgValue > 1) {
                                      averagePercent = avgValue;
                                    } else {
                                      averagePercent = avgValue * 100;
                                    }
                                  }
                                } else if (exam['average_raw_marks'] != null) {
                                  final rawMarks = exam['average_raw_marks'];
                                  if (rawMarks is num) {
                                    averagePercent = rawMarks.toDouble();
                                  }
                                }
                                
                                return _buildProgressCard(
                                  exam['exam_type'] ?? 'Exam',
                                  averagePercent,
                                  exam['grade'] ?? '-',
                                  exam['position'] ?? '-',
                                );
                              }),
                            ],
                          ),
                        ),
                      ],
                    ),
                  ),

          // ===== MENU 3: Assignments ===== //
          Container(
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
                      const Expanded(
                        child: Text(
                          'Kazi za Nyumbani',
                          style: TextStyle(
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

          // ===== MENU 4: Learning Materials ===== //
          Container(
            decoration: BoxDecoration(
              gradient: LinearGradient(
                begin: Alignment.topCenter,
                end: Alignment.bottomCenter,
                colors: [
                  Colors.teal.shade50,
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
                      colors: [Colors.teal.shade600, Colors.teal.shade400],
                      begin: Alignment.topLeft,
                      end: Alignment.bottomRight,
                    ),
                    borderRadius: BorderRadius.circular(16),
                    boxShadow: [
                      BoxShadow(
                        color: Colors.teal.withOpacity(0.3),
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
                          Icons.library_books,
                          color: Colors.white,
                          size: 28,
                        ),
                      ),
                      const SizedBox(width: 16),
                      const Expanded(
                        child: Text(
                          'Nyenzo za Kujifunza',
                          style: TextStyle(
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
                isLoadingMaterials
                    ? const Center(child: CircularProgressIndicator())
                    : libraryMaterials.isEmpty
                        ? Center(
                            child: Column(
                              mainAxisAlignment: MainAxisAlignment.center,
                              children: [
                                Icon(Icons.library_books_outlined, size: 64, color: Colors.grey.shade400),
                                const SizedBox(height: 16),
                                Text(
                                  'Hakuna nyenzo za kujifunza',
                                  style: TextStyle(
                                    fontSize: 16,
                                    color: Colors.grey.shade600,
                                  ),
                                ),
                              ],
                            ),
                          )
                        : ListView.builder(
                            shrinkWrap: true,
                            physics: const NeverScrollableScrollPhysics(),
                            itemCount: libraryMaterials.length,
                            itemBuilder: (context, index) {
                              final material = libraryMaterials[index];
                              IconData icon;
                              Color color;
                              
                              switch (material['type']) {
                                case 'pdf_book':
                                  icon = Icons.picture_as_pdf;
                                  color = Colors.red;
                                  break;
                                case 'notes':
                                  icon = Icons.note;
                                  color = Colors.blue;
                                  break;
                                case 'past_paper':
                                  icon = Icons.description;
                                  color = Colors.green;
                                  break;
                                case 'assignment':
                                  icon = Icons.assignment;
                                  color = Colors.orange;
                                  break;
                                default:
                                  icon = Icons.insert_drive_file;
                                  color = Colors.grey;
                              }
                              
                              return _buildMaterialCard(
                                material['title'] ?? 'Untitled',
                                icon,
                                color,
                                material: material,
                              );
                            },
                          ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  // ===== REUSABLE UI WIDGETS ===== //

  Widget _buildSubjectCard(String subjectName, Color startColor, Color endColor, int index) {
    final icons = [
      Icons.calculate,
      Icons.edit,
      Icons.science,
      Icons.language,
      Icons.computer,
      Icons.public,
      Icons.history_edu,
      Icons.psychology,
    ];
    
    return Container(
      decoration: BoxDecoration(
        gradient: LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [startColor, endColor],
        ),
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: startColor.withOpacity(0.3),
            blurRadius: 10,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          onTap: () {
            // TODO: Navigate to subject details
          },
          borderRadius: BorderRadius.circular(16),
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Container(
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: Colors.white.withOpacity(0.2),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Icon(
                    icons[index % icons.length],
                    color: Colors.white,
                    size: 32,
                  ),
                ),
                const SizedBox(height: 12),
                Text(
                  subjectName,
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 14,
                    fontWeight: FontWeight.bold,
                  ),
                  textAlign: TextAlign.center,
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildAssignmentsList() {
    if (assignmentsData == null) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.assignment_outlined, size: 64, color: Colors.grey.shade400),
            const SizedBox(height: 16),
            Text(
              'Hakuna kazi za nyumbani',
              style: TextStyle(
                fontSize: 16,
                color: Colors.grey.shade600,
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
            Icon(Icons.assignment_outlined, size: 64, color: Colors.grey.shade400),
            const SizedBox(height: 16),
            Text(
              'Hakuna kazi za nyumbani',
              style: TextStyle(
                fontSize: 16,
                color: Colors.grey.shade600,
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
          _buildSectionHeader('Zilizopita (Overdue)', Colors.red),
          ...overdue.map((item) => _buildAssignmentCardFromData(item, Colors.red, Icons.warning)),
          const SizedBox(height: 16),
        ],
        if (dueSoon.isNotEmpty) ...[
          _buildSectionHeader('Zinazokaribia (Due Soon)', Colors.orange),
          ...dueSoon.map((item) => _buildAssignmentCardFromData(item, Colors.orange, Icons.schedule)),
          const SizedBox(height: 16),
        ],
        if (upcoming.isNotEmpty) ...[
          _buildSectionHeader('Zinazokuja (Upcoming)', Colors.blue),
          ...upcoming.map((item) => _buildAssignmentCardFromData(item, Colors.blue, Icons.calendar_today)),
          const SizedBox(height: 16),
        ],
        if (submitted.isNotEmpty) ...[
          _buildSectionHeader('Zilizowasilishwa (Submitted)', Colors.green),
          ...submitted.map((item) => _buildAssignmentCardFromData(item, Colors.green, Icons.check_circle)),
          const SizedBox(height: 16),
        ],
        if (marked.isNotEmpty) ...[
          _buildSectionHeader('Zilizopimwa (Marked)', Colors.purple),
          ...marked.map((item) => _buildAssignmentCardFromData(item, Colors.purple, Icons.grade)),
        ],
      ],
    );
  }

  Widget _buildSectionHeader(String title, Color color) {
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
              color: color,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildAssignmentCardFromData(Map<String, dynamic> item, Color statusColor, IconData icon) {
    final title = item['title'] ?? item['subject'] ?? 'Assignment';
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

  Widget _buildAssignmentCard(String title, String assignedDate, String dueDate, String status, Color statusColor, IconData icon, Map<String, dynamic> assignmentData) {
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
              ScaffoldMessenger.of(context).showSnackBar(
                SnackBar(
                  content: Text('Hitilafu: Hatuwezi kufungua maelezo ya kazi hii'),
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
                              'Imepewa: ${_formatDate(assignedDate)}',
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
                            'Mwisho: ${_formatDate(dueDate)}',
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
                    'Maelezo',
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

  String _getDueText(String? dueDateString, String? dueTimeString) {
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
        return 'Imeisha $absDifference ${absDifference == 1 ? 'siku' : 'siku'} zilizopita';
      } else if (difference == 0) {
        // Check if it's today but past time
        if (dueDate.isBefore(now)) {
          return 'Imeisha leo';
        } else {
          return 'Leo';
        }
      } else if (difference == 1) {
        return 'Kesho';
      } else {
        return 'Siku $difference';
      }
    } catch (e) {
      print('Error parsing date: $e');
      return dueDateString;
    }
  }

  Widget _buildMaterialCard(String title, IconData icon, Color iconColor, {Map<String, dynamic>? material}) {
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
          onTap: () async {
            if (material != null && material['file_url'] != null) {
              // Open file URL
              try {
                final url = material['file_url'] as String;
                if (await canLaunchUrl(Uri.parse(url))) {
                  await launchUrl(Uri.parse(url), mode: LaunchMode.externalApplication);
                } else {
                  if (mounted) {
                    ScaffoldMessenger.of(context).showSnackBar(
                      const SnackBar(content: Text('Hatuwezi kufungua faili hili')),
                    );
                  }
                }
              } catch (e) {
                if (mounted) {
                  ScaffoldMessenger.of(context).showSnackBar(
                    SnackBar(content: Text('Hitilafu: $e')),
                  );
                }
              }
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
                    color: iconColor.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Icon(icon, color: iconColor, size: 24),
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: Text(
                    title,
                    style: const TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                ),
                Container(
                  padding: const EdgeInsets.all(8),
                  decoration: BoxDecoration(
                    color: Colors.blue.shade50,
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Icon(
                    Icons.download,
                    color: Colors.blue.shade700,
                    size: 20,
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildSummaryCard(String title, String value, IconData icon, Color color) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.05),
            blurRadius: 10,
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
                  color: color.withOpacity(0.1),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Icon(icon, color: color, size: 20),
              ),
              const Spacer(),
            ],
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
            title,
            style: TextStyle(
              fontSize: 12,
              color: Colors.grey.shade600,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildExamCard(BuildContext context, Map<String, dynamic> exam, int studentId) {
    final average = (exam['average_raw_marks'] ?? exam['average'] ?? 0.0).toDouble();
    final grade = exam['grade'] ?? '-';
    final position = exam['position'] ?? '-';
    final remark = exam['remark'] ?? '-';
    
    Color gradeColor = Colors.grey;
    if (grade == 'A') gradeColor = Colors.green;
    else if (grade == 'B') gradeColor = Colors.blue;
    else if (grade == 'C') gradeColor = Colors.orange;
    else if (grade == 'D' || grade == 'E') gradeColor = Colors.red;
    
    return Card(
      margin: const EdgeInsets.only(bottom: 16),
      elevation: 2,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      child: InkWell(
        onTap: () {
          // Navigate to results screen instead of ExamDetailPage
          Navigator.push(
            context,
            MaterialPageRoute(
              builder: (context) => const ExamsResultsScreen(),
            ),
          );
        },
        borderRadius: BorderRadius.circular(16),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: Colors.blue.shade50,
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: Icon(Icons.assignment_turned_in, color: Colors.blue.shade700, size: 24),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          exam['exam_type'] ?? 'Exam',
                          style: const TextStyle(
                            fontSize: 18,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                        const SizedBox(height: 4),
                        Text(
                          exam['academic_year'] ?? 'N/A',
                          style: TextStyle(
                            fontSize: 14,
                            color: Colors.grey.shade600,
                          ),
                        ),
                      ],
                    ),
                  ),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                    decoration: BoxDecoration(
                      color: gradeColor.withOpacity(0.1),
                      borderRadius: BorderRadius.circular(20),
                      border: Border.all(color: gradeColor, width: 2),
                    ),
                    child: Text(
                      grade,
                      style: TextStyle(
                        fontSize: 18,
                        fontWeight: FontWeight.bold,
                        color: gradeColor,
                      ),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 16),
              Row(
                children: [
                  Expanded(
                    child: _buildStatItem('Wastani', average.toStringAsFixed(1), Icons.bar_chart),
                  ),
                  Container(width: 1, height: 40, color: Colors.grey.shade300),
                  Expanded(
                    child: _buildStatItem(
                      'Jumla', 
                      '${exam['total_marks']?.toInt() ?? 0}${exam['max_marks'] != null && exam['max_marks'] > 0 ? '/${exam['max_marks']?.toInt() ?? 0}' : ''}', 
                      Icons.calculate
                    ),
                  ),
                  Container(width: 1, height: 40, color: Colors.grey.shade300),
                  Expanded(
                    child: _buildStatItem('Nafasi', position.toString(), Icons.emoji_events),
                  ),
                ],
              ),
              const SizedBox(height: 12),
              Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: Colors.grey.shade50,
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Row(
                  children: [
                    Icon(Icons.comment, size: 16, color: Colors.grey.shade600),
                    const SizedBox(width: 8),
                    Expanded(
                      child: Text(
                        remark,
                        style: TextStyle(
                          fontSize: 14,
                          fontWeight: FontWeight.w500,
                          color: Colors.grey.shade800,
                        ),
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 8),
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text(
                    '${exam['subjects_count'] ?? 0} Somo',
                    style: TextStyle(
                      fontSize: 12,
                      color: Colors.grey.shade600,
                    ),
                  ),
                  Row(
                    children: [
                      Text(
                        'Angalia Maelezo',
                        style: TextStyle(
                          fontSize: 12,
                          color: Colors.blue.shade700,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                      const SizedBox(width: 4),
                      Icon(Icons.arrow_forward_ios, size: 12, color: Colors.blue.shade700),
                    ],
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildStatItem(String label, String value, IconData icon) {
    return Column(
      children: [
        Icon(icon, size: 20, color: Colors.grey.shade600),
        const SizedBox(height: 4),
        Text(
          value,
          style: const TextStyle(
            fontSize: 16,
            fontWeight: FontWeight.bold,
          ),
        ),
        Text(
          label,
          style: TextStyle(
            fontSize: 11,
            color: Colors.grey.shade600,
          ),
        ),
      ],
    );
  }

  Widget _buildProgressCard(String examType, double average, String grade, String position) {
    final progressColor = _getProgressColor(average);
    final gradeColor = _getGradeColor(grade);
    
    return Container(
      margin: const EdgeInsets.only(bottom: 16),
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
      child: Row(
        children: [
          // Progress Circle
          Stack(
            alignment: Alignment.center,
            children: [
              SizedBox(
                width: 70,
                height: 70,
                child: CircularProgressIndicator(
                  value: average / 100,
                  strokeWidth: 6,
                  backgroundColor: progressColor.withOpacity(0.2),
                  valueColor: AlwaysStoppedAnimation<Color>(progressColor),
                ),
              ),
              Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text(
                    '${average.toStringAsFixed(0)}%',
                    style: TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.bold,
                      color: progressColor,
                    ),
                  ),
                  Text(
                    grade,
                    style: TextStyle(
                      fontSize: 12,
                      fontWeight: FontWeight.w600,
                      color: gradeColor,
                    ),
                  ),
                ],
              ),
            ],
          ),
          const SizedBox(width: 16),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  examType,
                  style: const TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.bold,
                  ),
                ),
                const SizedBox(height: 6),
                Row(
                  children: [
                    Icon(
                      Icons.emoji_events,
                      size: 14,
                      color: Colors.orange.shade600,
                    ),
                    const SizedBox(width: 4),
                    Text(
                      'Nafasi: $position',
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
            padding: const EdgeInsets.all(8),
            decoration: BoxDecoration(
              gradient: LinearGradient(
                colors: [gradeColor.withOpacity(0.2), gradeColor.withOpacity(0.1)],
              ),
              borderRadius: BorderRadius.circular(12),
            ),
            child: Icon(
              Icons.arrow_forward_ios,
              size: 16,
              color: gradeColor,
            ),
          ),
        ],
      ),
    );
  }

  Color _getProgressColor(double average) {
    if (average >= 80) return Colors.green;
    if (average >= 70) return Colors.blue;
    if (average >= 60) return Colors.orange;
    return Colors.red;
  }

  Color _getGradeColor(String grade) {
    if (grade == 'A') return Colors.green;
    if (grade == 'B') return Colors.blue;
    if (grade == 'C') return Colors.orange;
    if (grade == 'D' || grade == 'E') return Colors.red;
    return Colors.grey;
  }
}

