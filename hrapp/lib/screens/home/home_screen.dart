import 'package:flutter/material.dart';

import '../../services/auth_service.dart';
import '../../services/hr_service.dart';
import '../profile/profile_screen.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  int _bottomIndex = 0;
  Map<String, dynamic>? _user;

  @override
  void initState() {
    super.initState();
    _loadUser();
  }

  Future<void> _loadUser() async {
    final user = await AuthService.getCurrentUser();
    setState(() {
      _user = user;
    });
  }

  @override
  Widget build(BuildContext context) {
    final name = (_user?['name']?.toString().trim().isNotEmpty ?? false)
        ? _user!['name'].toString()
        : 'User';

    return Scaffold(
      backgroundColor: const Color(0xFFF2F5FA),
      body: SafeArea(
        child: Column(
          children: [
            Expanded(
              child: SingleChildScrollView(
                child: Column(
                  children: [
                    _HeroHeader(userName: name),
                    const SizedBox(height: 14),
                    _DashboardStats(),
                    const SizedBox(height: 18),
                    _SectionTitle(title: 'Employee Services'),
                    const SizedBox(height: 10),
                    _EmployeeServicesRow(),
                    const SizedBox(height: 18),
                    _SectionTitle(title: 'Quick Actions'),
                    const SizedBox(height: 10),
                    _QuickActionsRow(),
                    const SizedBox(height: 18),
                    _SectionTitle(title: 'Insights & Reports'),
                    const SizedBox(height: 10),
                    _InsightsRow(),
                    const SizedBox(height: 24),
                  ],
                ),
              ),
            ),
            _BottomNav(
              index: _bottomIndex,
              onTap: (i) async {
                setState(() => _bottomIndex = i);
                if (i == 3) {
                  // More
                  await Navigator.of(context).push(
                    MaterialPageRoute(builder: (_) => const ProfileScreen()),
                  );
                  if (!mounted) return;
                  setState(() => _bottomIndex = 0);
                }
              },
            ),
          ],
        ),
      ),
    );
  }
}

class _HeroHeader extends StatelessWidget {
  final String userName;
  const _HeroHeader({required this.userName});

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      decoration: const BoxDecoration(
        gradient: LinearGradient(
          begin: Alignment.topCenter,
          end: Alignment.bottomCenter,
          colors: [
            Color(0xFF1D66D4),
            Color(0xFF4A8EEA),
          ],
        ),
      ),
      child: Stack(
        children: [
          // Curved white bottom like the screenshot
          Positioned(
            left: 0,
            right: 0,
            bottom: -1,
            child: Container(
              height: 70,
              decoration: const BoxDecoration(
                color: Color(0xFFF2F5FA),
                borderRadius: BorderRadius.only(
                  topLeft: Radius.elliptical(420, 120),
                  topRight: Radius.elliptical(420, 120),
                ),
              ),
            ),
          ),
          Padding(
            padding: const EdgeInsets.fromLTRB(18, 16, 18, 16),
            child: Column(
              children: [
                // Top bar: title centered + avatar right
                Row(
                  children: [
                    const Spacer(),
                    Column(
                      children: [
                        const Text(
                          'HR & Payroll',
                          style: TextStyle(
                            color: Colors.white,
                            fontSize: 30,
                            fontWeight: FontWeight.w800,
                            letterSpacing: -0.4,
                          ),
                        ),
                        const SizedBox(height: 4),
                        const Text(
                          'Manage Your Workforce Efficiently',
                          style: TextStyle(
                            color: Colors.white70,
                            fontSize: 13,
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                      ],
                    ),
                    const Spacer(),
                    GestureDetector(
                      onTap: () async {
                        await Navigator.of(context).push(
                          MaterialPageRoute(
                              builder: (_) => const ProfileScreen()),
                        );
                      },
                      child: _Avatar(name: userName),
                    ),
                  ],
                ),
                const SizedBox(height: 12),
                // Illustration area (built with shapes to match the vibe)
                Container(
                  height: 150,
                  width: double.infinity,
                  decoration: BoxDecoration(
                    color: Colors.white.withOpacity(0.12),
                    borderRadius: BorderRadius.circular(18),
                    border: Border.all(color: Colors.white.withOpacity(0.18)),
                  ),
                  child: Stack(
                    children: [
                      Positioned(
                        left: 14,
                        top: 14,
                        child: _IconChip(icon: Icons.calendar_month, color: Colors.white),
                      ),
                      Positioned(
                        left: 64,
                        top: 14,
                        child: _IconChip(icon: Icons.fact_check, color: Colors.white),
                      ),
                      Positioned(
                        left: 114,
                        top: 14,
                        child: _IconChip(icon: Icons.attach_money, color: Colors.white),
                      ),
                      Positioned(
                        right: 16,
                        top: 18,
                        child: Container(
                          width: 44,
                          height: 44,
                          decoration: BoxDecoration(
                            color: Colors.white.withOpacity(0.16),
                            borderRadius: BorderRadius.circular(12),
                          ),
                          child: const Icon(Icons.checklist, color: Colors.white),
                        ),
                      ),
                      Align(
                        alignment: Alignment.bottomCenter,
                        child: Padding(
                          padding: const EdgeInsets.only(bottom: 18),
                          child: Row(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              _PersonIllustrationCard(isSeated: true),
                              const SizedBox(width: 18),
                              _PersonIllustrationCard(isSeated: false),
                            ],
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
    );
  }
}

class _Avatar extends StatelessWidget {
  final String name;
  const _Avatar({required this.name});

  @override
  Widget build(BuildContext context) {
    return Container(
      width: 44,
      height: 44,
      decoration: BoxDecoration(
        shape: BoxShape.circle,
        color: Colors.white,
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.12),
            blurRadius: 10,
            offset: const Offset(0, 6),
          ),
        ],
      ),
      child: Center(
        child: Text(
          name.isNotEmpty ? name[0].toUpperCase() : 'U',
          style: const TextStyle(
            fontWeight: FontWeight.w800,
            color: Color(0xFF1D66D4),
          ),
        ),
      ),
    );
  }
}

class _IconChip extends StatelessWidget {
  final IconData icon;
  final Color color;
  const _IconChip({required this.icon, required this.color});

  @override
  Widget build(BuildContext context) {
    return Container(
      width: 40,
      height: 40,
      decoration: BoxDecoration(
        color: Colors.white.withOpacity(0.16),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Icon(icon, color: color, size: 22),
    );
  }
}

class _PersonIllustrationCard extends StatelessWidget {
  final bool isSeated;
  const _PersonIllustrationCard({required this.isSeated});

  @override
  Widget build(BuildContext context) {
    return Container(
      width: 120,
      height: 76,
      decoration: BoxDecoration(
        color: Colors.white.withOpacity(0.16),
        borderRadius: BorderRadius.circular(16),
      ),
      child: Stack(
        children: [
          Positioned(
            left: 12,
            top: 14,
            child: Container(
              width: 26,
              height: 26,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                color: Colors.white.withOpacity(0.95),
              ),
            ),
          ),
          Positioned(
            left: 44,
            top: 18,
            child: Container(
              width: 58,
              height: 10,
              decoration: BoxDecoration(
                borderRadius: BorderRadius.circular(8),
                color: Colors.white.withOpacity(0.85),
              ),
            ),
          ),
          Positioned(
            left: 44,
            top: 34,
            child: Container(
              width: 42,
              height: 8,
              decoration: BoxDecoration(
                borderRadius: BorderRadius.circular(8),
                color: Colors.white.withOpacity(0.7),
              ),
            ),
          ),
          Positioned(
            left: 12,
            bottom: 14,
            child: Container(
              width: 96,
              height: 10,
              decoration: BoxDecoration(
                borderRadius: BorderRadius.circular(8),
                color: Colors.white.withOpacity(0.7),
              ),
            ),
          ),
          Positioned(
            right: 10,
            bottom: 12,
            child: Icon(
              isSeated ? Icons.laptop_mac : Icons.folder_open,
              color: Colors.white,
              size: 18,
            ),
          ),
        ],
      ),
    );
  }
}

// Dashboard Stats Widget
class _DashboardStats extends StatefulWidget {
  const _DashboardStats();

  @override
  State<_DashboardStats> createState() => _DashboardStatsState();
}

class _DashboardStatsState extends State<_DashboardStats> {
  Map<String, dynamic>? _stats;
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _loadStats();
  }

  Future<void> _loadStats() async {
    final result = await HrService.getDashboard();
    if (result['success'] == true && mounted) {
      final data = result['data'] ?? {};
      setState(() {
        _stats = {
          'leave_balance': data['total_leave_balance'] ?? 0.0,
          'pending_requests': data['pending_requests'] ?? 0,
          'net_pay': data['net_pay'] ?? 0.0,
          'profile_completeness': data['profile_completeness'] ?? 0,
        };
        _loading = false;
      });
    } else {
      if (mounted) {
        setState(() {
          _stats = {
            'leave_balance': 0.0,
            'pending_requests': 0,
            'net_pay': 0.0,
            'profile_completeness': 0,
          };
          _loading = false;
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_loading) {
      return const Padding(
        padding: EdgeInsets.symmetric(horizontal: 16),
        child: SizedBox(height: 100, child: Center(child: CircularProgressIndicator())),
      );
    }

    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16),
      child: Row(
        children: [
          Expanded(
            child: _StatCard(
              title: 'Leave Balance',
              value: '${_stats!['leave_balance']} days',
              icon: Icons.beach_access,
              color: const Color(0xFF4CAF50),
            ),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: _StatCard(
              title: 'Pending',
              value: '${_stats!['pending_requests']}',
              icon: Icons.pending_actions,
              color: const Color(0xFFFF9800),
            ),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: _StatCard(
              title: 'Net Pay',
              value: 'TZS ${(_stats!['net_pay'] / 1000).toStringAsFixed(0)}K',
              icon: Icons.account_balance_wallet,
              color: const Color(0xFF2196F3),
            ),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: _StatCard(
              title: 'Profile',
              value: '${_stats!['profile_completeness']}%',
              icon: Icons.person,
              color: const Color(0xFF9C27B0),
            ),
          ),
        ],
      ),
    );
  }
}

class _StatCard extends StatelessWidget {
  final String title;
  final String value;
  final IconData icon;
  final Color color;

  _StatCard({
    required this.title,
    required this.value,
    required this.icon,
    required this.color,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.06),
            blurRadius: 14,
            offset: const Offset(0, 8),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            width: 32,
            height: 32,
            decoration: BoxDecoration(
              color: color.withOpacity(0.1),
              borderRadius: BorderRadius.circular(8),
            ),
            child: Icon(icon, color: color, size: 18),
          ),
          const SizedBox(height: 8),
          Text(
            value,
            style: TextStyle(
              fontSize: 14,
              fontWeight: FontWeight.w800,
              color: color,
            ),
          ),
          const SizedBox(height: 2),
          Text(
            title,
            style: const TextStyle(
              fontSize: 10,
              fontWeight: FontWeight.w600,
              color: Color(0xFF6B7280),
            ),
          ),
        ],
      ),
    );
  }
}

// Employee Services Row
class _EmployeeServicesRow extends StatelessWidget {
  _EmployeeServicesRow();

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16),
      child: Column(
        children: [
          // Row 1: HR & Payroll Services
          Row(
            children: [
              Expanded(
                child: _SquareTile(
                  title: 'Leave\nManagement',
                  icon: Icons.calendar_today,
                  onTap: () {
                    Navigator.pushNamed(context, '/leave');
                  },
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: _SquareTile(
                  title: 'Attendance',
                  icon: Icons.access_time,
                  onTap: () {
                    Navigator.pushNamed(context, '/attendance');
                  },
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: _SquareTile(
                  title: 'Payslips',
                  icon: Icons.receipt_long,
                  onTap: () {
                    Navigator.pushNamed(context, '/payslips');
                  },
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: _SquareTile(
                  title: 'Loans &\nAdvances',
                  icon: Icons.account_balance,
                  onTap: () {
                    Navigator.pushNamed(context, '/loans');
                  },
                ),
              ),
            ],
          ),
          const SizedBox(height: 10),
          // Row 2: HR Services Continued
          Row(
            children: [
              Expanded(
                child: _SquareTile(
                  title: 'HR Requests',
                  icon: Icons.description,
                  onTap: () {
                    Navigator.pushNamed(context, '/hr-requests');
                  },
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: _SquareTile(
                  title: 'Benefits &\nStatutory',
                  icon: Icons.health_and_safety,
                  onTap: () {
                    Navigator.pushNamed(context, '/benefits');
                  },
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: _SquareTile(
                  title: 'Reports &\nAnalytics',
                  icon: Icons.insights,
                  onTap: () {
                    Navigator.pushNamed(context, '/reports');
                  },
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: _SquareTile(
                  title: 'Messages',
                  icon: Icons.message,
                  onTap: () {
                    Navigator.pushNamed(context, '/messages');
                  },
                ),
              ),
            ],
          ),
          const SizedBox(height: 10),
          // Row 3: Finance & Procurement
          Row(
            children: [
              Expanded(
                child: _SquareTile(
                  title: 'Imprest\nManagement',
                  icon: Icons.payments,
                  onTap: () {
                    Navigator.pushNamed(context, '/imprest');
                  },
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: _SquareTile(
                  title: 'Requisition',
                  icon: Icons.playlist_add_check,
                  onTap: () {
                    Navigator.pushNamed(context, '/requisition');
                  },
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: _SquareTile(
                  title: 'Store\nRequisition',
                  icon: Icons.store,
                  onTap: () {
                    Navigator.pushNamed(context, '/store-requisition');
                  },
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: _SquareTile(
                  title: 'Classes /\nCourses',
                  icon: Icons.class_,
                  onTap: () {
                    Navigator.pushNamed(context, '/classes');
                  },
                ),
              ),
            ],
          ),
          const SizedBox(height: 10),
          // Row 4: Academic Services
          Row(
            children: [
              Expanded(
                child: _SquareTile(
                  title: 'Exams &\nResults',
                  icon: Icons.school,
                  onTap: () {
                    Navigator.pushNamed(context, '/exams');
                  },
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: _SquareTile(
                  title: 'Homework',
                  icon: Icons.assignment,
                  onTap: () {
                    Navigator.pushNamed(context, '/homework');
                  },
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: _SquareTile(
                  title: 'Timetable',
                  icon: Icons.calendar_month,
                  onTap: () {
                    Navigator.pushNamed(context, '/timetable');
                  },
                ),
              ),
              const SizedBox(width: 10),
              const Expanded(child: SizedBox()),
            ],
          ),
        ],
      ),
    );
  }
}

class _SquareTile extends StatelessWidget {
  final String title;
  final IconData icon;
  final VoidCallback? onTap;
  _SquareTile({required this.title, required this.icon, this.onTap});

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        height: 110,
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(14),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withOpacity(0.06),
              blurRadius: 14,
              offset: const Offset(0, 8),
            ),
          ],
        ),
        child: Padding(
          padding: const EdgeInsets.all(8),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            mainAxisSize: MainAxisSize.min,
            children: [
              Container(
                width: 36,
                height: 36,
                decoration: BoxDecoration(
                  color: const Color(0xFFEAF2FF),
                  borderRadius: BorderRadius.circular(10),
                ),
                child: Icon(icon, color: const Color(0xFF1D66D4), size: 20),
              ),
              const SizedBox(height: 6),
              Expanded(
                child: Center(
                  child: Text(
                    title,
                    textAlign: TextAlign.center,
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(
                      fontSize: 10,
                      fontWeight: FontWeight.w700,
                      color: Color(0xFF1F2A44),
                      height: 1.1,
                    ),
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _SectionTitle extends StatelessWidget {
  final String title;
  _SectionTitle({required this.title});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16),
      child: Row(
        children: [
          Text(
            title,
            style: const TextStyle(
              fontSize: 18,
              fontWeight: FontWeight.w800,
              color: Color(0xFF1F2A44),
            ),
          ),
        ],
      ),
    );
  }
}

class _QuickActionsRow extends StatelessWidget {
  _QuickActionsRow();

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16),
      child: Row(
        children: [
          Expanded(
            child: _RoundTile(
              title: 'Apply Leave',
              icon: Icons.add_circle_outline,
              onTap: () {
                Navigator.pushNamed(context, '/leave/apply');
              },
            ),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: _RoundTile(
              title: 'View Payslip',
              icon: Icons.receipt_long,
              onTap: () {
                Navigator.pushNamed(context, '/payslips');
              },
            ),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: _RoundTile(
              title: 'Request Letter',
              icon: Icons.description,
              onTap: () {
                Navigator.pushNamed(context, '/hr-requests');
              },
            ),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: _RoundTile(
              title: 'My Approvals',
              icon: Icons.verified,
              onTap: () {
                Navigator.pushNamed(context, '/approvals');
              },
            ),
          ),
        ],
      ),
    );
  }
}

class _RoundTile extends StatelessWidget {
  final String title;
  final IconData icon;
  final VoidCallback? onTap;
  _RoundTile({required this.title, required this.icon, this.onTap});

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        height: 86,
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(16),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withOpacity(0.06),
              blurRadius: 14,
              offset: const Offset(0, 8),
            ),
          ],
        ),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Container(
              width: 44,
              height: 44,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                color: const Color(0xFFEAF2FF),
              ),
              child: Icon(icon, color: const Color(0xFF1D66D4)),
            ),
            const SizedBox(height: 8),
            Text(
              title,
              textAlign: TextAlign.center,
              style: const TextStyle(
                fontSize: 12,
                fontWeight: FontWeight.w700,
                color: Color(0xFF1F2A44),
                height: 1.1,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _InsightsRow extends StatelessWidget {
  _InsightsRow();

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16),
      child: Row(
        children: [
          Expanded(
            child: _ReportCard(
              title: 'Salary Summary',
              buttonText: 'View Report',
              icon: Icons.bar_chart,
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: _ReportCard(
              title: 'Requisition Status',
              buttonText: 'Check Status',
              icon: Icons.inventory_2,
            ),
          ),
        ],
      ),
    );
  }
}

class _ReportCard extends StatelessWidget {
  final String title;
  final String buttonText;
  final IconData icon;
  _ReportCard({
    required this.title,
    required this.buttonText,
    required this.icon,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      height: 150,
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.06),
            blurRadius: 14,
            offset: const Offset(0, 8),
          ),
        ],
      ),
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              title,
              style: const TextStyle(
                fontSize: 14,
                fontWeight: FontWeight.w800,
                color: Color(0xFF1F2A44),
              ),
            ),
            const SizedBox(height: 10),
            Expanded(
              child: Row(
                children: [
                  Container(
                    width: 34,
                    height: 34,
                    decoration: BoxDecoration(
                      shape: BoxShape.circle,
                      color: const Color(0xFFEAF2FF),
                    ),
                    child: const Icon(Icons.attach_money, color: Color(0xFF1D66D4)),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: Align(
                      alignment: Alignment.centerRight,
                      child: _MiniBars(icon: icon),
                    ),
                  ),
                ],
              ),
            ),
            SizedBox(
              width: double.infinity,
              height: 36,
              child: ElevatedButton(
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFF1D66D4),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(10),
                  ),
                ),
                onPressed: () {},
                child: Text(
                  buttonText,
                  style: const TextStyle(
                    fontWeight: FontWeight.w800,
                    color: Colors.white,
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _MiniBars extends StatelessWidget {
  final IconData icon;
  const _MiniBars({required this.icon});

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      width: 90,
      height: 44,
      child: CustomPaint(
        painter: _BarsPainter(),
        child: Align(
          alignment: Alignment.topRight,
          child: Icon(icon, color: const Color(0xFF1D66D4).withOpacity(0.15)),
        ),
      ),
    );
  }
}

class _BarsPainter extends CustomPainter {
  @override
  void paint(Canvas canvas, Size size) {
    final paint = Paint()..color = const Color(0xFF1D66D4);
    final barW = size.width / 8;
    final gaps = barW * 0.6;
    final baseY = size.height - 6;
    final heights = [10.0, 18.0, 12.0, 26.0, 20.0];
    for (var i = 0; i < heights.length; i++) {
      final x = (i * (barW + gaps));
      final h = heights[i];
      final rect = RRect.fromRectAndRadius(
        Rect.fromLTWH(x, baseY - h, barW, h),
        const Radius.circular(4),
      );
      canvas.drawRRect(rect, paint..color = const Color(0xFF1D66D4).withOpacity(0.55 + (i * 0.08)));
    }
  }

  @override
  bool shouldRepaint(covariant CustomPainter oldDelegate) => false;
}

class _BottomNav extends StatelessWidget {
  final int index;
  final ValueChanged<int> onTap;
  const _BottomNav({required this.index, required this.onTap});

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.08),
            blurRadius: 14,
            offset: const Offset(0, -6),
          ),
        ],
      ),
      child: SafeArea(
        top: false,
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 10),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              _NavItem(
                icon: Icons.home,
                label: 'Home',
                active: index == 0,
                onTap: () => onTap(0),
              ),
              _NavItem(
                icon: Icons.download,
                label: 'Tasks',
                active: index == 1,
                onTap: () => onTap(1),
              ),
              _NavItem(
                icon: Icons.notifications,
                label: 'Notifications',
                active: index == 2,
                badge: 2,
                onTap: () => onTap(2),
              ),
              _NavItem(
                icon: Icons.more_horiz,
                label: 'More',
                active: index == 3,
                onTap: () => onTap(3),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _NavItem extends StatelessWidget {
  final IconData icon;
  final String label;
  final bool active;
  final VoidCallback onTap;
  final int? badge;
  const _NavItem({
    required this.icon,
    required this.label,
    required this.active,
    required this.onTap,
    this.badge,
  });

  @override
  Widget build(BuildContext context) {
    final color = active ? const Color(0xFF1D66D4) : const Color(0xFF9AA6B2);
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(14),
      child: SizedBox(
        width: 78,
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Stack(
              clipBehavior: Clip.none,
              children: [
                Icon(icon, color: color, size: 28),
                if (badge != null && badge! > 0)
                  Positioned(
                    right: -6,
                    top: -6,
                    child: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                      decoration: BoxDecoration(
                        color: const Color(0xFFFF6A2A),
                        borderRadius: BorderRadius.circular(999),
                      ),
                      child: Text(
                        badge.toString(),
                        style: const TextStyle(
                          color: Colors.white,
                          fontSize: 10,
                          fontWeight: FontWeight.w800,
                        ),
                      ),
                    ),
                  ),
              ],
            ),
            const SizedBox(height: 4),
            Text(
              label,
              style: TextStyle(
                color: color,
                fontSize: 11,
                fontWeight: FontWeight.w700,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

