import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'main.dart'; // For ParentApiService
import 'language_provider.dart';

class AssignmentsPage extends StatefulWidget {
  const AssignmentsPage({super.key});

  @override
  State<AssignmentsPage> createState() => _AssignmentsPageState();
}

class _AssignmentsPageState extends State<AssignmentsPage> {
  final TextEditingController _searchCtrl = TextEditingController();
  String _filter = 'All';
  bool isLoading = true;
  int? studentId;
  
  Map<String, dynamic>? assignmentsData;

  @override
  void initState() {
    super.initState();
    _loadStudentId();
  }

  @override
  void dispose() {
    _searchCtrl.dispose();
    super.dispose();
  }

  Future<void> _loadStudentId() async {
    final prefs = await SharedPreferences.getInstance();
    final id = prefs.getInt('selected_student_id');
    if (id != null) {
      setState(() {
        studentId = id;
      });
      _loadAssignments();
    } else {
      setState(() {
        isLoading = false;
      });
    }
  }

  Future<void> _loadAssignments() async {
    if (studentId == null) return;
    
    setState(() {
      isLoading = true;
    });

    try {
      final data = await ParentApiService.getStudentAssignments(studentId!);
      if (mounted) {
        setState(() {
          assignmentsData = data;
          isLoading = false;
        });
      }
    } catch (e) {
      print('Error loading assignments: $e');
      if (mounted) {
        setState(() {
          isLoading = false;
        });
      }
    }
  }

  // Get lists from API data
  List<Map<String, dynamic>> get upcoming {
    if (assignmentsData == null) return [];
    final list = assignmentsData!['upcoming'] as List?;
    return list?.map((e) => Map<String, dynamic>.from(e)).toList() ?? [];
  }

  List<Map<String, dynamic>> get dueSoon {
    if (assignmentsData == null) return [];
    final list = assignmentsData!['due_soon'] as List?;
    return list?.map((e) => Map<String, dynamic>.from(e)).toList() ?? [];
  }

  List<Map<String, dynamic>> get submitted {
    if (assignmentsData == null) return [];
    final list = assignmentsData!['submitted'] as List?;
    return list?.map((e) => Map<String, dynamic>.from(e)).toList() ?? [];
  }

  List<Map<String, dynamic>> get marked {
    if (assignmentsData == null) return [];
    final list = assignmentsData!['marked'] as List?;
    return list?.map((e) => Map<String, dynamic>.from(e)).toList() ?? [];
  }

  List<Map<String, dynamic>> get overdue {
    if (assignmentsData == null) return [];
    final list = assignmentsData!['overdue'] as List?;
    return list?.map((e) => Map<String, dynamic>.from(e)).toList() ?? [];
  }

  // Filter methods
  bool _shouldShowAssignment(Map item) {
    final searchText = _searchCtrl.text.toLowerCase();
    final title = (item['title'] ?? '').toString().toLowerCase();
    final subject = (item['subject'] ?? '').toString().toLowerCase();
    
    // Search filter
    if (searchText.isNotEmpty) {
      if (!title.contains(searchText) && !subject.contains(searchText)) {
        return false;
      }
    }

    // Status filter
    if (_filter == 'All') {
      return true;
    } else if (_filter == 'Pending') {
      final status = item['status'] ?? '';
      return status == 'Pending' || status == 'Urgent' || status == 'Overdue' || 
             item.containsKey('daysLeft') || item.containsKey('dueIn');
    } else if (_filter == 'Submitted') {
      return item.containsKey('score') || item.containsKey('grade');
    }
    
    return true;
  }

  List<Map<String, dynamic>> _getFilteredList(List<Map<String, dynamic>> list) {
    return list.where((item) => _shouldShowAssignment(item)).toList();
  }

  @override
  Widget build(BuildContext context) {
    final languageProvider = Provider.of<LanguageProvider>(context);
    final trans = AppTranslations(languageProvider.currentLanguage);
    
    // Filter all lists
    final filteredUpcoming = _getFilteredList(upcoming);
    final filteredDueSoon = _getFilteredList(dueSoon);
    final filteredSubmitted = _getFilteredList(submitted);
    final filteredMarked = _getFilteredList(marked);
    final filteredOverdue = _getFilteredList(overdue);

    return Scaffold(
      backgroundColor: Colors.grey.shade50,
      appBar: AppBar(
        elevation: 0,
        flexibleSpace: Container(
          decoration: BoxDecoration(
            gradient: LinearGradient(
              colors: [Colors.orange.shade700, Colors.orange.shade500],
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
            ),
          ),
        ),
        title: Text(
          trans.get('assignments_title'),
          style: const TextStyle(
            fontWeight: FontWeight.bold,
            fontSize: 22,
            letterSpacing: 0.5,
            color: Colors.white,
          ),
        ),
        foregroundColor: Colors.white,
        actions: [
          IconButton(
            onPressed: () {
              _loadAssignments();
            },
            icon: const Icon(Icons.refresh),
            tooltip: 'Refresh',
          ),
        ],
      ),
      body: SafeArea(
        child: isLoading
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
                : RefreshIndicator(
                    onRefresh: _loadAssignments,
                    child: ListView(
                      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                      children: [
                        // Search & Filter Row
                        Row(
                          children: [
                            Expanded(
                              child: _buildSearchField(),
                            ),
                            const SizedBox(width: 10),
                            _filterChip('All'),
                            const SizedBox(width: 6),
                            _filterChip('Pending'),
                            const SizedBox(width: 6),
                            _filterChip('Submitted'),
                          ],
                        ),
                        const SizedBox(height: 18),

                        // Upcoming
                        if (filteredUpcoming.isNotEmpty) ...[
                          _sectionTitle('Upcoming Assignments'),
                          const SizedBox(height: 8),
                          for (final item in filteredUpcoming) _upcomingCard(item),
                          const SizedBox(height: 18),
                        ],

                        // Due Soon
                        if (filteredDueSoon.isNotEmpty) ...[
                          _sectionTitle('Due Soon'),
                          const SizedBox(height: 8),
                          for (final item in filteredDueSoon) _dueSoonCard(item),
                          const SizedBox(height: 18),
                        ],

                        // Submitted
                        if (filteredSubmitted.isNotEmpty) ...[
                          _sectionTitle('Submitted Assignments'),
                          const SizedBox(height: 8),
                          for (final item in filteredSubmitted) _submittedCard(item),
                          const SizedBox(height: 18),
                        ],

                        // Marked
                        if (filteredMarked.isNotEmpty) ...[
                          _sectionTitle('Marked Assignments'),
                          const SizedBox(height: 8),
                          for (final item in filteredMarked) _markedCard(item),
                          const SizedBox(height: 18),
                        ],

                        // Overdue
                        if (filteredOverdue.isNotEmpty) ...[
                          _sectionTitle('Overdue / Missed'),
                          const SizedBox(height: 8),
                          for (final item in filteredOverdue) _overdueCard(item),
                          const SizedBox(height: 24),
                        ],

                        // Show message if no results
                        if (filteredUpcoming.isEmpty &&
                            filteredDueSoon.isEmpty &&
                            filteredSubmitted.isEmpty &&
                            filteredMarked.isEmpty &&
                            filteredOverdue.isEmpty) ...[
                          const SizedBox(height: 60),
                          Center(
                            child: Column(
                              children: [
                                Icon(Icons.search_off, size: 64, color: Colors.grey.shade400),
                                const SizedBox(height: 16),
                                Text(
                                  'Hakuna matokeo',
                                  style: TextStyle(
                                    fontSize: 18,
                                    fontWeight: FontWeight.w600,
                                    color: Colors.grey.shade600,
                                  ),
                                ),
                                const SizedBox(height: 8),
                                Text(
                                  'Jaribu kubadilisha filter au search',
                                  style: TextStyle(color: Colors.grey.shade500),
                                ),
                              ],
                            ),
                          ),
                          const SizedBox(height: 60),
                        ],

                        // Bottom CTA
                        ElevatedButton.icon(
                          onPressed: () {
                            ScaffoldMessenger.of(context).showSnackBar(
                              const SnackBar(content: Text('Open help / add screen (not implemented)')),
                            );
                          },
                          icon: const Icon(Icons.add),
                          label: const Text('Ask Teacher / Add Note'),
                          style: ElevatedButton.styleFrom(
                            minimumSize: const Size.fromHeight(48),
                            backgroundColor: Colors.orange.shade700,
                            foregroundColor: Colors.white,
                          ),
                        ),
                        const SizedBox(height: 24),
                      ],
                    ),
                  ),
      ),
    );
  }

  Widget _buildSearchField() {
    return TextField(
      controller: _searchCtrl,
      style: const TextStyle(color: Colors.black87),
      decoration: InputDecoration(
        hintText: 'Search assignments...',
        hintStyle: TextStyle(color: Colors.grey.shade600),
        prefixIcon: Icon(Icons.search, color: Colors.grey.shade700),
        filled: true,
        fillColor: Colors.white,
        contentPadding: const EdgeInsets.symmetric(vertical: 14),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: BorderSide(color: Colors.grey.shade300),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: BorderSide(color: Colors.grey.shade300),
        ),
      ),
      onChanged: (v) {
        setState(() {});
      },
    );
  }

  Widget _filterChip(String name) {
    final selected = _filter == name;
    return ChoiceChip(
      label: Text(
        name,
        style: TextStyle(
          color: selected ? Colors.white : Colors.black87,
          fontWeight: selected ? FontWeight.w600 : FontWeight.normal,
        ),
      ),
      selected: selected,
      onSelected: (_) => setState(() => _filter = name),
      selectedColor: Colors.orange.shade700,
      backgroundColor: Colors.white,
      elevation: 0,
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
      side: BorderSide(color: Colors.grey.shade300),
    );
  }

  Widget _sectionTitle(String title) {
    return Text(
      title,
      style: const TextStyle(
        fontSize: 18,
        fontWeight: FontWeight.w700,
        color: Colors.black87,
      ),
    );
  }

  Widget _upcomingCard(Map<String, dynamic> item) {
    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      elevation: 2,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 16),
        child: Row(
          children: [
            // Icon
            Container(
              height: 54,
              width: 54,
              decoration: BoxDecoration(
                color: Colors.blue.shade700,
                borderRadius: BorderRadius.circular(10),
              ),
              child: const Icon(Icons.menu_book_rounded, color: Colors.white, size: 28),
            ),
            const SizedBox(width: 12),
            // Texts
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    item['subject'] ?? '',
                    style: TextStyle(fontSize: 14, color: Colors.grey.shade700),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    item['title'] ?? '',
                    style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w700, color: Colors.black87),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    item['due'] ?? '',
                    style: TextStyle(color: Colors.grey.shade600),
                  ),
                  const SizedBox(height: 8),
                  Row(
                    children: [
                      Chip(
                        label: Text(
                          item['status'] ?? 'Pending',
                          style: const TextStyle(color: Colors.white, fontSize: 11),
                        ),
                        backgroundColor: Colors.orange.shade700,
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                      ),
                    ],
                  )
                ],
              ),
            ),

            // Days left
            Text(
              item['daysLeft'] ?? '',
              style: const TextStyle(
                color: Colors.blue,
                fontWeight: FontWeight.w700,
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _dueSoonCard(Map<String, dynamic> item) {
    return Card(
      color: Colors.red.shade50,
      margin: const EdgeInsets.only(bottom: 12),
      elevation: 2,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(12),
        side: BorderSide(color: Colors.red.shade200),
      ),
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 16),
        child: Row(
          children: [
            // bell icon
            Container(
              height: 54,
              width: 54,
              decoration: BoxDecoration(
                color: Colors.red.shade700,
                borderRadius: BorderRadius.circular(10),
              ),
              child: const Icon(Icons.notifications_active_outlined, color: Colors.white, size: 26),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    item['title'] ?? '',
                    style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w700, color: Colors.black87),
                  ),
                  const SizedBox(height: 6),
                  Text(
                    item['subject'] ?? '',
                    style: TextStyle(color: Colors.grey.shade700, fontSize: 14),
                  ),
                  const SizedBox(height: 6),
                  Text(
                    item['dueIn'] ?? '',
                    style: TextStyle(color: Colors.grey.shade700),
                  ),
                  const SizedBox(height: 10),
                  Row(
                    children: [
                      ElevatedButton(
                        onPressed: () {
                          ScaffoldMessenger.of(context).showSnackBar(
                            const SnackBar(content: Text('Reminder set')),
                          );
                        },
                        style: ElevatedButton.styleFrom(
                          backgroundColor: Colors.red.shade700,
                          foregroundColor: Colors.white,
                          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                        ),
                        child: const Text('Remind Me'),
                      ),
                      const SizedBox(width: 10),
                      IconButton(
                        onPressed: () {},
                        icon: Icon(Icons.more_vert, color: Colors.grey.shade700),
                      )
                    ],
                  )
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _submittedCard(Map<String, dynamic> item) {
    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      elevation: 2,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      child: ListTile(
        contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
        leading: const Icon(Icons.check_circle_outline, color: Colors.green, size: 36),
        title: Text(
          item['title'] ?? '',
          style: const TextStyle(fontWeight: FontWeight.w700, color: Colors.black87),
        ),
        subtitle: Text(
          '✔ Submitted • ${item['subject'] ?? ''}',
          style: TextStyle(color: Colors.grey.shade700),
        ),
        trailing: Text(
          item['score'] ?? 'Awaiting Mark',
          style: const TextStyle(fontWeight: FontWeight.w700, color: Colors.black87),
        ),
        onTap: () {
          // Open detail
        },
      ),
    );
  }

  Widget _markedCard(Map<String, dynamic> item) {
    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      elevation: 2,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Expanded(
                  child: Text(
                    item['title'] ?? '',
                    style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 16, color: Colors.black87),
                  ),
                ),
                if (item['grade'] != null)
                  Text(
                    item['grade'] ?? '',
                    style: const TextStyle(color: Colors.green, fontWeight: FontWeight.bold, fontSize: 18),
                  ),
              ],
            ),
            const SizedBox(height: 8),
            Text(
              'Score: ${item['score'] ?? ''} • ${item['subject'] ?? ''}',
              style: TextStyle(color: Colors.grey.shade700),
            ),
            if (item['feedback'] != null) ...[
              const SizedBox(height: 8),
              const Text(
                'Teacher feedback:',
                style: TextStyle(color: Colors.black87, fontWeight: FontWeight.w600),
              ),
              const SizedBox(height: 6),
              Text(
                item['feedback'] ?? '',
                style: TextStyle(color: Colors.grey.shade700),
              ),
            ],
          ],
        ),
      ),
    );
  }

  Widget _overdueCard(Map<String, dynamic> item) {
    return Card(
      color: Colors.orange.shade50,
      margin: const EdgeInsets.only(bottom: 12),
      elevation: 2,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 14),
        child: Row(
          children: [
            Container(
              height: 54,
              width: 54,
              decoration: BoxDecoration(
                color: Colors.deepOrange.shade700,
                borderRadius: BorderRadius.circular(10),
              ),
              child: const Icon(Icons.warning_amber_rounded, color: Colors.white, size: 28),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    item['title'] ?? '',
                    style: const TextStyle(fontWeight: FontWeight.w700, color: Colors.black87),
                  ),
                  const SizedBox(height: 6),
                  Text(
                    item['subject'] ?? '',
                    style: TextStyle(color: Colors.grey.shade700),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    item['days'] ?? '',
                    style: TextStyle(color: Colors.grey.shade600),
                  ),
                ],
              ),
            ),
            ElevatedButton(
              onPressed: () {
                ScaffoldMessenger.of(context).showSnackBar(
                  const SnackBar(content: Text('Open submit screen')),
                );
              },
              style: ElevatedButton.styleFrom(
                backgroundColor: Colors.deepOrange,
                foregroundColor: Colors.white,
                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
              ),
              child: const Text('Submit now'),
            )
          ],
        ),
      ),
    );
  }
}

