import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'services/auth_service.dart';
import 'screens/auth/login_screen.dart';
import 'screens/home/home_screen.dart';
import 'screens/profile/profile_screen.dart';
import 'screens/common/coming_soon_screen.dart';
import 'screens/imprest/imprest_list_screen.dart';
import 'screens/requisition/requisition_list_screen.dart';
import 'screens/retirement/retirement_list_screen.dart';

void main() {
  WidgetsFlutterBinding.ensureInitialized();
  SystemChrome.setPreferredOrientations([
    DeviceOrientation.portraitUp,
    DeviceOrientation.portraitDown,
  ]);
  runApp(const MyApp());
}

class MyApp extends StatelessWidget {
  const MyApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'HR App',
      debugShowCheckedModeBanner: false,
      theme: ThemeData(
        primarySwatch: Colors.blue,
        scaffoldBackgroundColor: const Color(0xFFF6F7F8),
        useMaterial3: true,
      ),
      routes: {
        '/profile': (context) => const ProfileScreen(),

        // HR modules (placeholders until screens are implemented)
        '/leave': (context) => const ComingSoonScreen(
              title: 'Leave Management',
              icon: Icons.calendar_today,
            ),
        '/leave/apply': (context) => const ComingSoonScreen(
              title: 'Apply Leave',
              icon: Icons.add_circle_outline,
            ),
        '/attendance': (context) => const ComingSoonScreen(
              title: 'Attendance',
              icon: Icons.access_time,
            ),
        '/payslips': (context) => const ComingSoonScreen(
              title: 'Payslips',
              icon: Icons.receipt_long,
            ),
        '/loans': (context) => const ComingSoonScreen(
              title: 'Loans & Advances',
              icon: Icons.account_balance,
            ),
        '/hr-requests': (context) => const ComingSoonScreen(
              title: 'HR Requests & Letters',
              icon: Icons.description,
            ),
        '/benefits': (context) => const ComingSoonScreen(
              title: 'Benefits & Statutory',
              icon: Icons.health_and_safety,
            ),
        '/notifications': (context) => const ComingSoonScreen(
              title: 'Notifications',
              icon: Icons.notifications,
            ),
        '/approvals': (context) => const ComingSoonScreen(
              title: 'Manager Approvals',
              icon: Icons.verified,
            ),

        // Imprest Management - IMPLEMENTED
        '/imprest': (context) => const ImprestListScreen(),
        
        // Retirement Management - IMPLEMENTED
        '/retirement': (context) => const RetirementListScreen(),
        
        // Requisition - redirects to Store Requisition
        '/requisition': (context) => const RequisitionListScreen(),
        
        // Store Requisition - IMPLEMENTED
        '/store-requisition': (context) => const RequisitionListScreen(),
        '/exams': (context) => const ComingSoonScreen(
              title: 'Exams & Results',
              icon: Icons.school,
            ),
        '/homework': (context) => const ComingSoonScreen(
              title: 'Homework / Assignments',
              icon: Icons.assignment,
            ),
        '/timetable': (context) => const ComingSoonScreen(
              title: 'Timetable',
              icon: Icons.calendar_month,
            ),
        '/messages': (context) => const ComingSoonScreen(
              title: 'Messages / Communication',
              icon: Icons.message,
            ),
        '/reports': (context) => const ComingSoonScreen(
              title: 'Reports & Analytics',
              icon: Icons.insights,
            ),
        '/classes': (context) => const ComingSoonScreen(
              title: 'Classes / Courses',
              icon: Icons.class_,
            ),
      },
      home: const AuthWrapper(),
    );
  }
}

class AuthWrapper extends StatefulWidget {
  const AuthWrapper({super.key});

  @override
  State<AuthWrapper> createState() => _AuthWrapperState();
}

class _AuthWrapperState extends State<AuthWrapper> {
  bool _isLoading = true;
  bool _isLoggedIn = false;

  @override
  void initState() {
    super.initState();
    _checkAuth();
  }

  Future<void> _checkAuth() async {
    final loggedIn = await AuthService.isLoggedIn();
    setState(() {
      _isLoggedIn = loggedIn;
      _isLoading = false;
    });
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

    return _isLoggedIn ? const HomeScreen() : const LoginScreen();
  }
}
