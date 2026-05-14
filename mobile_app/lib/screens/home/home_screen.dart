import 'package:flutter/material.dart';
import 'dart:async';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:provider/provider.dart';
import '../../services/parent_api_service.dart';
import '../../providers/language_provider.dart';
import '../../providers/theme_provider.dart';
import '../exams/exams_screen.dart'; // Contains AcademicsPage
import '../assignments/assignments_screen.dart';
import '../attendance/attendance_screen.dart';
import '../fees/fees_screen.dart';
import '../results/results_screen.dart';
import '../profile/profile_screen.dart';
import '../select_student.dart';
import '../auth/login_screen.dart';
import '../timetable/timetable_screen.dart';
import '../messages/messages_screen.dart';
import '../settings/settings_screen.dart';
import '../library/library_screen.dart';

// Reusable UI Components
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

// Main Home Page
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
  int unreadNotificationCount = 0;
  Timer? _notificationTimer;

  @override
  void initState() {
    super.initState();
    _loadSelectedStudentId();
    _loadGuardianData();
    _loadUnreadNotifications();
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
    // Refresh notification count every 30 seconds
    _notificationTimer = Timer.periodic(const Duration(seconds: 30), (Timer timer) {
      _loadUnreadNotifications();
    });
  }

  @override
  void dispose() {
    _timer?.cancel();
    _notificationTimer?.cancel();
    _carouselController.dispose();
    super.dispose();
  }

  Future<void> _loadUnreadNotifications() async {
    try {
      final count = await ParentApiService.getUnreadNotificationsCount(
        studentId: selectedStudentId,
      );
      if (mounted) {
        setState(() {
          unreadNotificationCount = count ?? 0;
        });
      }
    } catch (e) {
      print('Error loading unread notifications: $e');
    }
  }

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    // Refresh student ID when screen is shown (e.g., after student change)
    _refreshStudentData();
  }

  Future<void> _refreshStudentData() async {
    final prefs = await SharedPreferences.getInstance();
    final newStudentId = prefs.getInt('selected_student_id');
    
    // If student ID changed, reload data
    if (newStudentId != selectedStudentId) {
      setState(() {
        selectedStudentId = newStudentId;
      });
      if (newStudentId != null) {
        _loadExams(newStudentId);
      }
    }
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
      // Fallback to saved data
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

  void _onBottomNavTap(int idx) {
    print('Bottom nav tapped: $idx, current index: $_selectedIndex');
    
    // If clicking the same tab that's already selected, do nothing
    if (idx == _selectedIndex) {
      print('Same tab clicked, ignoring');
      return;
    }

    // Update index for visual feedback
    setState(() {
      _selectedIndex = idx;
    });

    // Navigate to different pages based on index
    try {
      switch (idx) {
        case 0:
          // Home - already here, do nothing
          print('Home tab clicked');
          break;
        case 1:
          // Matokeo (Results)
          print('Navigating to Results');
          Navigator.push(
            context,
            MaterialPageRoute(builder: (context) => const ExamsResultsScreen()),
          ).then((_) {
            // Reset to home when coming back
            if (mounted) {
              setState(() {
                _selectedIndex = 0;
              });
            }
          }).catchError((error) {
            print('Error navigating to Results: $error');
            if (mounted) {
              setState(() {
                _selectedIndex = 0;
              });
            }
          });
          break;
        case 2:
          // Ujumbe (Messages)
          print('Navigating to Messages');
          Navigator.push(
            context,
            MaterialPageRoute(builder: (context) => const MessagesPage()),
          ).then((_) {
            // Reset to home when coming back
            if (mounted) {
              setState(() {
                _selectedIndex = 0;
              });
            }
          }).catchError((error) {
            print('Error navigating to Messages: $error');
            if (mounted) {
              setState(() {
                _selectedIndex = 0;
              });
            }
          });
          break;
        case 3:
          // Mipangilio (Settings)
          print('Navigating to Settings');
          Navigator.push(
            context,
            MaterialPageRoute(builder: (context) => const SettingsPage()),
          ).then((_) {
            // Reset to home when coming back
            if (mounted) {
              setState(() {
                _selectedIndex = 0;
              });
            }
          }).catchError((error) {
            print('Error navigating to Settings: $error');
            if (mounted) {
              setState(() {
                _selectedIndex = 0;
              });
            }
          });
          break;
      }
    } catch (e) {
      print('Error in _onBottomNavTap: $e');
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Hitilafu: Hatuwezi kufungua ukurasa huu: $e'),
          backgroundColor: Colors.red,
        ),
      );
      // Reset index on error
      if (mounted) {
        setState(() {
          _selectedIndex = 0;
        });
      }
    }
  }

  // Helper function to convert student ID to int
  int? _getStudentId(dynamic id) {
    if (id == null) return null;
    if (id is int) return id;
    return int.tryParse(id.toString());
  }

  Future<void> _changeStudent() async {
    // Navigate to student selection page
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
    // Clear selected student ID
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove('selected_student_id');
    if (mounted) {
      Navigator.of(context).pushReplacement(
        MaterialPageRoute(builder: (context) => LoginScreen()),
      );
    }
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

  @override
  Widget build(BuildContext context) {
    // Get selected student based on selectedStudentId, or first student, or use defaults
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
    const double feesPaidPercent = 0.65; // 65%
    
    final int? currentStudentId = selectedStudentId ?? _getStudentId(student?['id']);
    final languageProvider = Provider.of<LanguageProvider>(context);
    final themeProvider = Provider.of<ThemeProvider>(context);
    final trans = AppTranslations(languageProvider.currentLanguage);
    final isDark = themeProvider.isDarkMode;

    return Scaffold(
      backgroundColor: isDark ? const Color(0xFF101115) : Colors.grey.shade50,
      appBar: AppBar(
        flexibleSpace: Container(
          decoration: BoxDecoration(
            gradient: LinearGradient(
              colors: isDark 
                  ? [const Color(0xFF16181F), const Color(0xFF1A1D24)]
                  : [Colors.blue.shade700, Colors.blue.shade500],
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
              trans.get('welcome'),
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
              Navigator.push(
                context,
                MaterialPageRoute(
                  builder: (context) => const MessagesPage(),
                ),
              ).then((_) {
                // Refresh notification count when returning from messages
                _loadUnreadNotifications();
              });
            },
            icon: Stack(
              children: [
                const Icon(Icons.message, color: Colors.white, size: 26),
                if (unreadNotificationCount > 0)
                  Positioned(
                    right: 0,
                    top: 0,
                    child: Container(
                      padding: const EdgeInsets.all(4),
                      decoration: const BoxDecoration(
                        color: Colors.red,
                        shape: BoxShape.circle,
                      ),
                      constraints: const BoxConstraints(
                        minWidth: 16,
                        minHeight: 16,
                      ),
                      child: Text(
                        unreadNotificationCount > 9 ? '9+' : '$unreadNotificationCount',
                        style: const TextStyle(
                          color: Colors.white,
                          fontSize: 10,
                          fontWeight: FontWeight.bold,
                        ),
                        textAlign: TextAlign.center,
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
                                ],
                              ),
                            ),
                          )
                        : Padding(
                            padding: const EdgeInsets.all(20),
                            child: Text(
                              trans.get('no_student_info'),
                              style: const TextStyle(color: Colors.white),
                            ),
                          ),
                  ),

                  // Features Section
                  Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 16),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          trans.get('services'),
                          style: TextStyle(
                            fontSize: 20,
                            fontWeight: FontWeight.bold,
                            color: isDark ? const Color(0xFFE4E5E6) : Colors.black87,
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
                              label: trans.get('subjects'),
                              color: Colors.purple,
                              onTap: () {
                                if (student != null) {
                                  Navigator.push(
                                    context,
                                    MaterialPageRoute(
                                      builder: (context) => AcademicsPage(studentId: currentStudentId ?? _getStudentId(student?['id']) ?? 0),
                                    ),
                                  );
                                }
                              },
                            ),
                            _FeatureTile(
                              icon: Icons.assignment,
                              label: trans.get('homework'),
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
                              label: trans.get('attendance'),
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
                              label: trans.get('fees'),
                              color: Colors.teal,
                              onTap: () {
                                if (student != null) {
                                  Navigator.push(
                                    context,
                                    MaterialPageRoute(
                                      builder: (context) => FeesPage(studentId: currentStudentId ?? _getStudentId(student?['id']) ?? 0),
                                    ),
                                  );
                                }
                              },
                            ),
                            _FeatureTile(
                              icon: Icons.schedule,
                              label: trans.get('timetable'),
                              color: Colors.indigo,
                              onTap: () {
                                if (student != null) {
                                  Navigator.push(
                                    context,
                                    MaterialPageRoute(
                                      builder: (context) => TimetablePage(studentId: currentStudentId ?? _getStudentId(student?['id']) ?? 0),
                                    ),
                                  );
                                }
                              },
                            ),
                            _FeatureTile(
                              icon: Icons.library_books,
                              label: trans.get('school_library'),
                              color: Colors.purple,
                              onTap: () {
                                if (student != null) {
                                  Navigator.push(
                                    context,
                                    MaterialPageRoute(
                                      builder: (context) => LibraryPage(studentId: currentStudentId ?? _getStudentId(student?['id']) ?? 0),
                                    ),
                                  );
                                }
                              },
                            ),
                            _FeatureTile(
                              icon: Icons.emoji_events,
                              label: trans.get('results'),
                              color: Colors.amber,
                              onTap: () {
                                if (student != null) {
                                  Navigator.push(
                                    context,
                                    MaterialPageRoute(
                                      builder: (context) => const ExamsResultsScreen(),
                                    ),
                                  );
                                }
                              },
                            ),
                            _FeatureTile(
                              icon: Icons.message,
                              label: trans.get('messages'),
                              color: Colors.cyan,
                              onTap: () {
                                Navigator.push(
                                  context,
                                  MaterialPageRoute(
                                    builder: (context) => const MessagesPage(),
                                  ),
                                ).then((_) {
                                  // Refresh notification count when returning from messages
                                  if (mounted) {
                                    _loadUnreadNotifications();
                                  }
                                });
                              },
                            ),
                            _FeatureTile(
                              icon: Icons.more_horiz,
                              label: trans.get('more'),
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
        backgroundColor: isDark ? const Color(0xFF16181F) : Colors.white,
        type: BottomNavigationBarType.fixed,
        items: [
          BottomNavigationBarItem(icon: const Icon(Icons.home), label: trans.get('nav_home')),
          BottomNavigationBarItem(icon: const Icon(Icons.bar_chart), label: trans.get('nav_results')),
          BottomNavigationBarItem(icon: const Icon(Icons.message), label: trans.get('nav_messages')),
          BottomNavigationBarItem(icon: const Icon(Icons.settings), label: trans.get('nav_settings')),
        ],
      ),
    );
  }
}
