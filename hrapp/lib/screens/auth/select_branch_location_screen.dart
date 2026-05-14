import 'package:flutter/material.dart';
import '../../services/auth_service.dart';
import '../home/home_screen.dart';

class SelectBranchLocationScreen extends StatefulWidget {
  final Map<String, dynamic> user;
  const SelectBranchLocationScreen({super.key, required this.user});

  @override
  State<SelectBranchLocationScreen> createState() => _SelectBranchLocationScreenState();
}

class _SelectBranchLocationScreenState extends State<SelectBranchLocationScreen> {
  int? _selectedBranchId;
  int? _selectedLocationId;
  bool _submitting = false;
  Map<String, dynamic>? _currentUser;

  List<Map<String, dynamic>> get _branches {
    final user = _currentUser ?? widget.user;
    final b = user['branches'];
    if (b is List) {
      return b.cast<Map<String, dynamic>>();
    }
    return const [];
  }

  List<Map<String, dynamic>> get _locations {
    final user = _currentUser ?? widget.user;
    final l = user['locations'];
    if (l is List) {
      return l.cast<Map<String, dynamic>>();
    }
    return const [];
  }

  List<Map<String, dynamic>> get _filteredLocations {
    if (_selectedBranchId == null) return [];
    return _locations.where((loc) => (loc['branch_id'] == _selectedBranchId)).toList();
  }

  @override
  void initState() {
    super.initState();
    _loadUserData();
  }

  Future<void> _loadUserData() async {
    final user = await AuthService.getCurrentUser();
    if (user != null) {
      setState(() {
        _currentUser = user;
        // Update branches and locations from fresh data
        final branches = (user['branches'] as List?) ?? [];
        final locations = (user['locations'] as List?) ?? [];
        
        // Preselect defaults from API if present
        final branchId = user['branch_id'];
        final locationId = user['location_id'];
        if (branchId is int) _selectedBranchId = branchId;
        if (locationId is int) _selectedLocationId = locationId;

        // If only one branch, auto-select
        if (branches.length == 1) {
          _selectedBranchId = branches.first['id'] as int?;
        }
        // If only one location for that branch, auto-select
        final filtered = locations.where((l) => l['branch_id'] == _selectedBranchId).toList();
        if (filtered.length == 1) {
          _selectedLocationId = filtered.first['id'] as int?;
        }
      });
    } else {
      // Fallback to widget.user if no fresh data
      setState(() {
        _currentUser = widget.user;
        final branchId = widget.user['branch_id'];
        final locationId = widget.user['location_id'];
        if (branchId is int) _selectedBranchId = branchId;
        if (locationId is int) _selectedLocationId = locationId;

        if (_branches.length == 1) {
          _selectedBranchId = _branches.first['id'] as int?;
        }
        final locs = _filteredLocations;
        if (locs.length == 1) {
          _selectedLocationId = locs.first['id'] as int?;
        }
      });
    }
  }

  Future<void> _continue() async {
    if (_selectedBranchId == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Row(
            children: const [
              Icon(Icons.error_outline, color: Colors.white),
              SizedBox(width: 12),
              Text('Please select a branch'),
            ],
          ),
          backgroundColor: const Color(0xFFE53935),
          behavior: SnackBarBehavior.floating,
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
        ),
      );
      return;
    }
    setState(() {
      _submitting = true;
    });
    await AuthService.setSelectedContext(
      branchId: _selectedBranchId!,
      locationId: _selectedLocationId,
    );
    if (!mounted) return;
    setState(() {
      _submitting = false;
    });
    Navigator.of(context).pushAndRemoveUntil(
      MaterialPageRoute(builder: (_) => const HomeScreen()),
      (route) => false,
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Container(
        decoration: const BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topCenter,
            end: Alignment.bottomCenter,
            colors: [
              Color(0xFF4A90E2),
              Color(0xFF5BA3F5),
              Color(0xFFF2F5FA),
            ],
            stops: [0.0, 0.4, 0.4],
          ),
        ),
        child: SafeArea(
          child: SingleChildScrollView(
            child: Column(
              children: [
                // Compact Header Section
                Container(
                  padding: const EdgeInsets.fromLTRB(24, 16, 24, 0),
                  child: Column(
                    children: [
                      Container(
                        width: 50,
                        height: 50,
                        decoration: BoxDecoration(
                          color: Colors.white,
                          borderRadius: BorderRadius.circular(12),
                          boxShadow: [
                            BoxShadow(
                              color: Colors.black.withOpacity(0.08),
                              blurRadius: 8,
                              offset: const Offset(0, 2),
                            ),
                          ],
                        ),
                        child: const Icon(
                          Icons.business,
                          color: Color(0xFF4A90E2),
                          size: 26,
                        ),
                      ),
                      const SizedBox(height: 16),
                      Container(
                        height: 100,
                        decoration: BoxDecoration(
                          color: Colors.white.withOpacity(0.12),
                          borderRadius: BorderRadius.circular(16),
                        ),
                        child: Stack(
                          children: [
                            Positioned(
                              top: 12,
                              left: 20,
                              child: Container(
                                width: 32,
                                height: 32,
                                decoration: BoxDecoration(
                                  color: Colors.white.withOpacity(0.25),
                                  shape: BoxShape.circle,
                                ),
                                child: const Icon(Icons.location_on, color: Colors.white, size: 18),
                              ),
                            ),
                            Positioned(
                              bottom: 12,
                              right: 20,
                              child: Container(
                                width: 50,
                                height: 50,
                                decoration: BoxDecoration(
                                  color: Colors.white.withOpacity(0.25),
                                  borderRadius: BorderRadius.circular(12),
                                ),
                                child: const Icon(Icons.store, color: Colors.white, size: 26),
                              ),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ),

                // White Form Section
                Container(
                  margin: const EdgeInsets.only(top: 12),
                  decoration: const BoxDecoration(
                    color: Color(0xFFF2F5FA),
                    borderRadius: BorderRadius.only(
                      topLeft: Radius.circular(32),
                      topRight: Radius.circular(32),
                    ),
                  ),
                  child: Padding(
                    padding: const EdgeInsets.all(24.0),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const SizedBox(height: 8),
                        const Text(
                          'Choose Branch & Location',
                          style: TextStyle(
                            fontSize: 24,
                            fontWeight: FontWeight.w800,
                            color: Color(0xFF1F2A44),
                            letterSpacing: -0.5,
                          ),
                        ),
                        const SizedBox(height: 12),
                        Text(
                          'Select your working branch and location',
                          style: TextStyle(
                            fontSize: 15,
                            fontWeight: FontWeight.w500,
                            color: Colors.grey[700],
                            height: 1.4,
                          ),
                        ),
                        const SizedBox(height: 32),

                        // Branch Selection
                        _DropdownField(
                          label: 'Branch',
                          value: _selectedBranchId,
                          items: _branches
                              .map(
                                (b) => DropdownMenuItem<int>(
                                  value: b['id'] as int?,
                                  child: Text(b['name']?.toString() ?? 'Branch'),
                                ),
                              )
                              .toList(),
                          onChanged: (val) {
                            setState(() {
                              _selectedBranchId = val;
                              _selectedLocationId = null;
                            });
                          },
                          icon: Icons.business,
                        ),
                        const SizedBox(height: 24),

                        // Location Selection
                        _DropdownField(
                          label: 'Location (Optional)',
                          value: _selectedLocationId,
                          items: _filteredLocations
                              .map(
                                (l) => DropdownMenuItem<int>(
                                  value: l['id'] as int?,
                                  child: Text(l['name']?.toString() ?? 'Location'),
                                ),
                              )
                              .toList(),
                          onChanged: _selectedBranchId == null
                              ? null
                              : (val) {
                                  setState(() {
                                    _selectedLocationId = val;
                                  });
                                },
                          icon: Icons.location_on,
                          enabled: _selectedBranchId != null,
                        ),
                        const SizedBox(height: 40),

                        // Continue Button
                        SizedBox(
                          width: double.infinity,
                          height: 56,
                          child: ElevatedButton(
                            style: ElevatedButton.styleFrom(
                              backgroundColor: const Color(0xFF4A90E2),
                              shape: RoundedRectangleBorder(
                                borderRadius: BorderRadius.circular(16),
                              ),
                              elevation: 0,
                            ),
                            onPressed: _submitting ? null : _continue,
                            child: _submitting
                                ? const SizedBox(
                                    width: 24,
                                    height: 24,
                                    child: CircularProgressIndicator(
                                      strokeWidth: 2.5,
                                      valueColor: AlwaysStoppedAnimation<Color>(Colors.white),
                                    ),
                                  )
                                : const Text(
                                    'Continue',
                                    style: TextStyle(
                                      fontWeight: FontWeight.w700,
                                      fontSize: 16,
                                      color: Colors.white,
                                      letterSpacing: 0.5,
                                    ),
                                  ),
                          ),
                        ),
                        const SizedBox(height: 32),
                      ],
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
}

class _DropdownField extends StatelessWidget {
  final String label;
  final int? value;
  final List<DropdownMenuItem<int>> items;
  final ValueChanged<int?>? onChanged;
  final IconData icon;
  final bool enabled;

  const _DropdownField({
    required this.label,
    required this.value,
    required this.items,
    required this.onChanged,
    required this.icon,
    this.enabled = true,
  });

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          label,
          style: const TextStyle(
            fontSize: 14,
            fontWeight: FontWeight.w600,
            color: Color(0xFF374151),
          ),
        ),
        const SizedBox(height: 8),
        Container(
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(16),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withOpacity(0.04),
                blurRadius: 10,
                offset: const Offset(0, 4),
              ),
            ],
          ),
          child: DropdownButtonFormField<int>(
            value: value,
            items: items,
            onChanged: enabled ? onChanged : null,
            decoration: InputDecoration(
              prefixIcon: Icon(icon, color: Colors.grey[400], size: 22),
              filled: true,
              fillColor: enabled ? Colors.white : Colors.grey[100],
              border: OutlineInputBorder(
                borderRadius: BorderRadius.circular(16),
                borderSide: BorderSide.none,
              ),
              contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 18),
            ),
            style: TextStyle(
              fontSize: 16,
              fontWeight: FontWeight.w600,
              color: enabled ? const Color(0xFF1F2A44) : Colors.grey[400],
            ),
            icon: Icon(Icons.arrow_drop_down, color: Colors.grey[400]),
          ),
        ),
      ],
    );
  }
}
