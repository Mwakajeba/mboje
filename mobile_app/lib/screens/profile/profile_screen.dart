import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../../services/parent_api_service.dart';
import '../home/home_screen.dart';

// Profile Page
class ProfilePage extends StatefulWidget {
  const ProfilePage({super.key});

  @override
  State<ProfilePage> createState() => _ProfilePageState();
}

class _ProfilePageState extends State<ProfilePage> {
  Map<String, dynamic>? profileData;
  List<Map<String, dynamic>> students = [];
  int? selectedStudentId;
  bool isLoading = true;
  bool isEditing = false;
  final TextEditingController _nameController = TextEditingController();
  final TextEditingController _emailController = TextEditingController();
  final TextEditingController _phoneController = TextEditingController();
  final TextEditingController _altPhoneController = TextEditingController();
  final TextEditingController _addressController = TextEditingController();
  final TextEditingController _occupationController = TextEditingController();
  final TextEditingController _passwordController = TextEditingController();
  final TextEditingController _passwordConfirmController =
      TextEditingController();

  @override
  void initState() {
    super.initState();
    _loadProfile();
    _loadSelectedStudent();
  }

  Future<void> _loadSelectedStudent() async {
    final prefs = await SharedPreferences.getInstance();
    setState(() {
      selectedStudentId = prefs.getInt('selected_student_id');
    });
  }

  Future<void> _loadProfile() async {
    final data = await ParentApiService.getGuardianInfo();
    if (mounted) {
      setState(() {
        profileData = data;
        if (data != null) {
          _nameController.text = data['name'] ?? '';
          _emailController.text = data['email'] ?? '';
          _phoneController.text = data['phone'] ?? '';
          _altPhoneController.text = data['alt_phone'] ?? '';
          _addressController.text = data['address'] ?? '';
          _occupationController.text = data['occupation'] ?? '';
          students = List<Map<String, dynamic>>.from(data['students'] ?? []);
        }
        isLoading = false;
      });
    }
  }

  Future<void> _changeStudent(int studentId) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setInt('selected_student_id', studentId);
    
    setState(() {
      selectedStudentId = studentId;
    });

    if (mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Student changed successfully'),
          backgroundColor: Colors.green,
        ),
      );
      
      // Navigate back to home to refresh data
      Navigator.of(context).pushAndRemoveUntil(
        MaterialPageRoute(builder: (context) => const HomePage()),
        (route) => false,
      );
    }
  }

  void _showStudentSelector() {
    if (students.length <= 1) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('You only have one student'),
          backgroundColor: Colors.blue,
        ),
      );
      return;
    }

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (context) => Container(
        padding: const EdgeInsets.all(20),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                const Text(
                  'Select Student',
                  style: TextStyle(
                    fontSize: 20,
                    fontWeight: FontWeight.bold,
                  ),
                ),
                IconButton(
                  icon: const Icon(Icons.close),
                  onPressed: () => Navigator.pop(context),
                ),
              ],
            ),
            const SizedBox(height: 16),
            Flexible(
              child: ListView.builder(
                shrinkWrap: true,
                itemCount: students.length,
                itemBuilder: (context, index) {
                  final student = students[index];
                  final studentId = student['id'] is int
                      ? student['id'] as int
                      : int.tryParse(student['id'].toString()) ?? 0;
                  final isSelected = selectedStudentId == studentId;
                  
                  String studentName = student['name']?.toString() ?? 
                      (student['full_name']?.toString() ?? 'Student');
                  
                  String className = 'N/A';
                  if (student['class'] != null) {
                    if (student['class'] is Map) {
                      className = student['class']?['name']?.toString() ?? 'N/A';
                    } else if (student['class'] is String) {
                      className = student['class'] as String;
                    }
                  }
                  
                  String streamName = '';
                  if (student['stream'] != null) {
                    if (student['stream'] is Map) {
                      streamName = student['stream']?['name']?.toString() ?? '';
                    } else if (student['stream'] is String) {
                      streamName = student['stream'] as String;
                    }
                  }
                  
                  final studentGrade = streamName.isNotEmpty
                      ? '$className - $streamName'
                      : className;

                  return Card(
                    margin: const EdgeInsets.only(bottom: 12),
                    elevation: isSelected ? 4 : 1,
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(12),
                      side: BorderSide(
                        color: isSelected ? Colors.purple : Colors.transparent,
                        width: isSelected ? 2 : 0,
                      ),
                    ),
                    child: ListTile(
                      leading: CircleAvatar(
                        backgroundColor: isSelected
                            ? Colors.purple.shade100
                            : Colors.grey.shade200,
                        child: Icon(
                          Icons.person,
                          color: isSelected ? Colors.purple : Colors.grey,
                        ),
                      ),
                      title: Text(
                        studentName,
                        style: TextStyle(
                          fontWeight: isSelected
                              ? FontWeight.bold
                              : FontWeight.normal,
                          color: isSelected ? Colors.purple : Colors.black87,
                        ),
                      ),
                      subtitle: Text(studentGrade),
                      trailing: isSelected
                          ? const Icon(Icons.check_circle, color: Colors.purple)
                          : null,
                      onTap: () {
                        _changeStudent(studentId);
                        Navigator.pop(context);
                      },
                    ),
                  );
                },
              ),
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _updateProfile() async {
    final updateData = {
      'name': _nameController.text,
      'email': _emailController.text,
      'phone': _phoneController.text,
      'alt_phone': _altPhoneController.text,
      'address': _addressController.text,
      'occupation': _occupationController.text,
    };

    if (_passwordController.text.isNotEmpty) {
      if (_passwordController.text != _passwordConfirmController.text) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Passwords do not match')),
        );
        return;
      }
      updateData['password'] = _passwordController.text;
      updateData['password_confirmation'] = _passwordConfirmController.text;
    }

    final result = await ParentApiService.updateProfile(updateData);
    if (mounted) {
      if (result != null) {
        setState(() {
          isEditing = false;
          profileData = result;
        });
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Profile updated successfully')),
        );
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Failed to update profile')),
        );
      }
    }
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
              colors: [Colors.purple.shade700, Colors.purple.shade500],
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
            ),
          ),
        ),
        title: const Text(
          'Wasifu Wangu',
          style: TextStyle(
            fontWeight: FontWeight.bold,
            fontSize: 22,
            letterSpacing: 0.5,
          ),
        ),
        foregroundColor: Colors.white,
        actions: [
          if (!isEditing)
            IconButton(
              icon: const Icon(Icons.edit),
              onPressed: () {
                setState(() {
                  isEditing = true;
                });
              },
              tooltip: 'Hariri',
            )
          else
            IconButton(
              icon: const Icon(Icons.save),
              onPressed: _updateProfile,
              tooltip: 'Hifadhi',
            ),
        ],
      ),
      body: isLoading
          ? Center(
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  CircularProgressIndicator(
                    valueColor:
                        AlwaysStoppedAnimation<Color>(Colors.purple.shade700),
                  ),
                  const SizedBox(height: 16),
                  Text(
                    'Inapakia...',
                    style: TextStyle(
                      color: Colors.grey.shade600,
                      fontSize: 14,
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
                    Colors.purple.shade50,
                    Colors.grey.shade50,
                  ],
                ),
              ),
              child: SingleChildScrollView(
                padding: const EdgeInsets.all(16),
                child: Column(
                  children: [
                    // Profile Header Card
                    Container(
                      padding: const EdgeInsets.all(24),
                      decoration: BoxDecoration(
                        gradient: LinearGradient(
                          colors: [
                            Colors.purple.shade700,
                            Colors.purple.shade500
                          ],
                          begin: Alignment.topLeft,
                          end: Alignment.bottomRight,
                        ),
                        borderRadius: BorderRadius.circular(20),
                        boxShadow: [
                          BoxShadow(
                            color: Colors.purple.withOpacity(0.3),
                            blurRadius: 15,
                            offset: const Offset(0, 5),
                          ),
                        ],
                      ),
                      child: Column(
                        children: [
                          Stack(
                            children: [
                              Container(
                                width: 100,
                                height: 100,
                                decoration: BoxDecoration(
                                  shape: BoxShape.circle,
                                  color: Colors.white,
                                  border: Border.all(
                                    color: Colors.white,
                                    width: 4,
                                  ),
                                  boxShadow: [
                                    BoxShadow(
                                      color: Colors.black.withOpacity(0.2),
                                      blurRadius: 10,
                                      offset: const Offset(0, 4),
                                    ),
                                  ],
                                ),
                                child: const Icon(
                                  Icons.person,
                                  size: 60,
                                  color: Colors.purple,
                                ),
                              ),
                              if (isEditing)
                                Positioned(
                                  bottom: 0,
                                  right: 0,
                                  child: Container(
                                    padding: const EdgeInsets.all(8),
                                    decoration: BoxDecoration(
                                      color: Colors.white,
                                      shape: BoxShape.circle,
                                      boxShadow: [
                                        BoxShadow(
                                          color: Colors.black.withOpacity(0.2),
                                          blurRadius: 5,
                                          offset: const Offset(0, 2),
                                        ),
                                      ],
                                    ),
                                    child: const Icon(
                                      Icons.camera_alt,
                                      size: 20,
                                      color: Colors.purple,
                                    ),
                                  ),
                                ),
                            ],
                          ),
                          const SizedBox(height: 16),
                          Text(
                            profileData?['name'] ?? 'Parent',
                            style: const TextStyle(
                              color: Colors.white,
                              fontSize: 24,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                          const SizedBox(height: 4),
                          Text(
                            profileData?['email'] ?? '',
                            style: TextStyle(
                              color: Colors.white.withOpacity(0.9),
                              fontSize: 14,
                            ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 24),
                    // Student Selection Card (if multiple students)
                    if (students.length > 1)
                      Container(
                        margin: const EdgeInsets.only(bottom: 16),
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
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Container(
                              padding: const EdgeInsets.all(16),
                              decoration: BoxDecoration(
                                color: Colors.blue.shade50,
                                borderRadius: const BorderRadius.only(
                                  topLeft: Radius.circular(16),
                                  topRight: Radius.circular(16),
                                ),
                              ),
                              child: Row(
                                children: [
                                  Icon(Icons.swap_horiz,
                                      color: Colors.blue.shade700),
                                  const SizedBox(width: 12),
                                  const Text(
                                    'Change Student',
                                    style: TextStyle(
                                      fontSize: 18,
                                      fontWeight: FontWeight.bold,
                                      color: Colors.black87,
                                    ),
                                  ),
                                ],
                              ),
                            ),
                            Padding(
                              padding: const EdgeInsets.all(16),
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    'Currently viewing:',
                                    style: TextStyle(
                                      fontSize: 14,
                                      color: Colors.grey.shade600,
                                    ),
                                  ),
                                  const SizedBox(height: 8),
                                  Builder(
                                    builder: (context) {
                                      final currentStudent = students.firstWhere(
                                        (s) {
                                          final id = s['id'] is int
                                              ? s['id'] as int
                                              : int.tryParse(
                                                      s['id'].toString()) ??
                                                  0;
                                          return id == selectedStudentId;
                                        },
                                        orElse: () => students[0],
                                      );
                                      final studentName = currentStudent['name']
                                              ?.toString() ??
                                          (currentStudent['full_name']
                                                  ?.toString() ??
                                              'Student');
                                      String className = 'N/A';
                                      if (currentStudent['class'] != null) {
                                        if (currentStudent['class'] is Map) {
                                          className = currentStudent['class']
                                                  ?['name']
                                                  ?.toString() ??
                                              'N/A';
                                        } else if (currentStudent['class']
                                            is String) {
                                          className =
                                              currentStudent['class'] as String;
                                        }
                                      }
                                      String streamName = '';
                                      if (currentStudent['stream'] != null) {
                                        if (currentStudent['stream'] is Map) {
                                          streamName = currentStudent['stream']
                                                  ?['name']
                                                  ?.toString() ??
                                              '';
                                        } else if (currentStudent['stream']
                                            is String) {
                                          streamName = currentStudent['stream']
                                              as String;
                                        }
                                      }
                                      final studentGrade = streamName.isNotEmpty
                                          ? '$className - $streamName'
                                          : className;

                                      return Row(
                                        children: [
                                          Expanded(
                                            child: Column(
                                              crossAxisAlignment:
                                                  CrossAxisAlignment.start,
                                              children: [
                                                Text(
                                                  studentName,
                                                  style: const TextStyle(
                                                    fontSize: 16,
                                                    fontWeight: FontWeight.bold,
                                                  ),
                                                ),
                                                const SizedBox(height: 4),
                                                Text(
                                                  studentGrade,
                                                  style: TextStyle(
                                                    fontSize: 14,
                                                    color: Colors.grey.shade600,
                                                  ),
                                                ),
                                              ],
                                            ),
                                          ),
                                        ],
                                      );
                                    },
                                  ),
                                  const SizedBox(height: 16),
                                  SizedBox(
                                    width: double.infinity,
                                    child: OutlinedButton.icon(
                                      onPressed: _showStudentSelector,
                                      icon: const Icon(Icons.swap_horiz),
                                      label: const Text('Change Student'),
                                      style: OutlinedButton.styleFrom(
                                        foregroundColor: Colors.blue.shade700,
                                        side: BorderSide(
                                            color: Colors.blue.shade700),
                                        padding: const EdgeInsets.symmetric(
                                            vertical: 12),
                                        shape: RoundedRectangleBorder(
                                          borderRadius: BorderRadius.circular(8),
                                        ),
                                      ),
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          ],
                        ),
                      ),
                    // Personal Information Card
                    _buildInfoCard(
                      'Taarifa Binafsi',
                      Icons.person_outline,
                      [
                        _buildInfoField(
                            Icons.badge, 'Jina', _nameController, isEditing),
                        _buildInfoField(Icons.email, 'Barua Pepe',
                            _emailController, isEditing,
                            keyboardType: TextInputType.emailAddress),
                        _buildInfoField(
                            Icons.phone, 'Simu', _phoneController, isEditing,
                            keyboardType: TextInputType.phone),
                        _buildInfoField(Icons.phone_android, 'Simu Mbadala',
                            _altPhoneController, isEditing,
                            keyboardType: TextInputType.phone),
                      ],
                    ),
                    const SizedBox(height: 16),
                    // Additional Information Card
                    _buildInfoCard(
                      'Taarifa Zaidi',
                      Icons.info_outline,
                      [
                        _buildInfoField(
                            Icons.home, 'Anuani', _addressController, isEditing,
                            maxLines: 3),
                        _buildInfoField(Icons.work, 'Kazi',
                            _occupationController, isEditing),
                      ],
                    ),
                    if (isEditing) ...[
                      const SizedBox(height: 16),
                      // Password Change Card
                      _buildInfoCard(
                        'Badilisha Nenosiri',
                        Icons.lock_outline,
                        [
                          _buildInfoField(Icons.lock, 'Nenosiri Jipya',
                              _passwordController, true,
                              password: true),
                          _buildInfoField(
                              Icons.lock_reset,
                              'Thibitisha Nenosiri',
                              _passwordConfirmController,
                              true,
                              password: true),
                        ],
                      ),
                      const SizedBox(height: 24),
                      // Save Button
                      SizedBox(
                        width: double.infinity,
                        height: 50,
                        child: ElevatedButton(
                          onPressed: _updateProfile,
                          style: ElevatedButton.styleFrom(
                            backgroundColor: Colors.purple.shade700,
                            foregroundColor: Colors.white,
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(12),
                            ),
                            elevation: 5,
                          ),
                          child: const Text(
                            'Hifadhi Mabadiliko',
                            style: TextStyle(
                              fontSize: 16,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                        ),
                      ),
                      const SizedBox(height: 16),
                      // Cancel Button
                      SizedBox(
                        width: double.infinity,
                        height: 50,
                        child: OutlinedButton(
                          onPressed: () {
                            setState(() {
                              isEditing = false;
                              _passwordController.clear();
                              _passwordConfirmController.clear();
                            });
                          },
                          style: OutlinedButton.styleFrom(
                            foregroundColor: Colors.purple.shade700,
                            side: BorderSide(
                                color: Colors.purple.shade700, width: 2),
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(12),
                            ),
                          ),
                          child: const Text(
                            'Ghairi',
                            style: TextStyle(
                              fontSize: 16,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                        ),
                      ),
                    ],
                  ],
                ),
              ),
            ),
    );
  }

  Widget _buildInfoCard(String title, IconData icon, List<Widget> children) {
    return Container(
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
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: Colors.purple.shade50,
              borderRadius: const BorderRadius.only(
                topLeft: Radius.circular(16),
                topRight: Radius.circular(16),
              ),
            ),
            child: Row(
              children: [
                Icon(icon, color: Colors.purple.shade700),
                const SizedBox(width: 12),
                Text(
                  title,
                  style: TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.bold,
                    color: Colors.purple.shade700,
                  ),
                ),
              ],
            ),
          ),
          Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              children: children,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildInfoField(IconData icon, String label,
      TextEditingController controller, bool enabled,
      {TextInputType? keyboardType, int maxLines = 1, bool password = false}) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 16),
      child: TextField(
        controller: controller,
        enabled: enabled,
        keyboardType: keyboardType,
        maxLines: maxLines,
        obscureText: password,
        decoration: InputDecoration(
          labelText: label,
          prefixIcon: Icon(icon, color: Colors.purple.shade700),
          border: OutlineInputBorder(
            borderRadius: BorderRadius.circular(12),
            borderSide: BorderSide(color: Colors.grey.shade300),
          ),
          enabledBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(12),
            borderSide: BorderSide(color: Colors.grey.shade300),
          ),
          focusedBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(12),
            borderSide: BorderSide(color: Colors.purple.shade700, width: 2),
          ),
          filled: !enabled,
          fillColor: enabled ? Colors.white : Colors.grey.shade100,
          contentPadding:
              const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
        ),
      ),
    );
  }

  @override
  void dispose() {
    _nameController.dispose();
    _emailController.dispose();
    _phoneController.dispose();
    _altPhoneController.dispose();
    _addressController.dispose();
    _occupationController.dispose();
    _passwordController.dispose();
    _passwordConfirmController.dispose();
    super.dispose();
  }
}

// Student Details Page
class StudentDetailsPage extends StatefulWidget {
  final int studentId;
  const StudentDetailsPage({super.key, required this.studentId});

  @override
  State<StudentDetailsPage> createState() => _StudentDetailsPageState();
}

class _StudentDetailsPageState extends State<StudentDetailsPage> {
  Map<String, dynamic>? studentData;
  bool isLoading = true;

  @override
  void initState() {
    super.initState();
    _loadStudentDetails();
  }

  Future<void> _loadStudentDetails() async {
    final data = await ParentApiService.getStudentDetails(widget.studentId);
    if (mounted) {
      setState(() {
        studentData = data;
        isLoading = false;
      });
    }
  }

  String _formatDate(String? dateString) {
    if (dateString == null || dateString == 'N/A') return 'N/A';
    try {
      final date = DateTime.parse(dateString);
      return '${date.day}/${date.month}/${date.year}';
    } catch (e) {
      return dateString;
    }
  }

  Widget _buildInfoCard({
    required String title,
    required IconData icon,
    required List<Widget> children,
  }) {
    return Container(
      margin: const EdgeInsets.all(16),
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
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: Colors.blue.shade50,
              borderRadius: const BorderRadius.only(
                topLeft: Radius.circular(12),
                topRight: Radius.circular(12),
              ),
            ),
            child: Row(
              children: [
                Icon(icon, color: Colors.blue, size: 24),
                const SizedBox(width: 12),
                Text(
                  title,
                  style: const TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.bold,
                    color: Colors.black87,
                  ),
                ),
              ],
            ),
          ),
          Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              children: children,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildInfoItem(String label, String value, IconData icon) {
    final hasValue = value != 'N/A' && value.isNotEmpty;
    return Container(
      margin: const EdgeInsets.only(bottom: 16),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            width: 40,
            height: 40,
            decoration: BoxDecoration(
              color: hasValue ? Colors.blue.shade50 : Colors.grey.shade100,
              borderRadius: BorderRadius.circular(8),
            ),
            child: Icon(
              icon,
              size: 20,
              color: hasValue ? Colors.blue : Colors.grey,
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  label,
                  style: TextStyle(
                    fontSize: 12,
                    color: Colors.grey.shade600,
                    fontWeight: FontWeight.w500,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  value,
                  style: TextStyle(
                    fontSize: 16,
                    color: hasValue ? Colors.black87 : Colors.grey,
                    fontWeight: hasValue ? FontWeight.w500 : FontWeight.normal,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.grey.shade100,
      appBar: AppBar(
        title: const Text('Student Details'),
        backgroundColor: Colors.blue,
        foregroundColor: Colors.white,
        elevation: 0,
      ),
      body: isLoading
          ? const Center(child: CircularProgressIndicator())
          : studentData == null
              ? const Center(child: Text('Student not found'))
              : SingleChildScrollView(
                  child: Column(
                    children: [
                      // Header Section with Student Photo and Name
                      Container(
                        width: double.infinity,
                        decoration: const BoxDecoration(
                          gradient: LinearGradient(
                            colors: [Color(0xFF2E9AFE), Color(0xFF7FC7FF)],
                            begin: Alignment.topLeft,
                            end: Alignment.bottomRight,
                          ),
                        ),
                        padding: const EdgeInsets.all(24),
                        child: Column(
                          children: [
                            CircleAvatar(
                              radius: 50,
                              backgroundColor: Colors.white,
                              child: Icon(
                                Icons.person,
                                size: 60,
                                color: Colors.blue.shade300,
                              ),
                            ),
                            const SizedBox(height: 16),
                            Text(
                              studentData!['full_name'] ?? 'Student',
                              style: const TextStyle(
                                fontSize: 24,
                                fontWeight: FontWeight.bold,
                                color: Colors.white,
                              ),
                            ),
                            const SizedBox(height: 8),
                            if (studentData!['admission_number'] != null)
                              Container(
                                padding: const EdgeInsets.symmetric(
                                    horizontal: 12, vertical: 6),
                                decoration: BoxDecoration(
                                  color: Colors.white.withOpacity(0.2),
                                  borderRadius: BorderRadius.circular(20),
                                ),
                                child: Text(
                                  'Admission: ${studentData!['admission_number']}',
                                  style: const TextStyle(
                                    color: Colors.white,
                                    fontSize: 14,
                                  ),
                                ),
                              ),
                          ],
                        ),
                      ),

                      // Personal Information Card
                      _buildInfoCard(
                        title: 'Personal Information',
                        icon: Icons.person,
                        children: [
                          _buildInfoItem('Full Name',
                              studentData!['full_name'] ?? 'N/A', Icons.badge),
                          _buildInfoItem(
                              'Date of Birth',
                              _formatDate(studentData!['date_of_birth']),
                              Icons.calendar_today),
                          _buildInfoItem('Gender',
                              studentData!['gender'] ?? 'N/A', Icons.wc),
                          _buildInfoItem('Email',
                              studentData!['email'] ?? 'N/A', Icons.email),
                          _buildInfoItem(
                              'Address',
                              studentData!['address'] ?? 'N/A',
                              Icons.location_on),
                        ],
                      ),

                      // Academic Information Card
                      _buildInfoCard(
                        title: 'Academic Information',
                        icon: Icons.school,
                        children: [
                          _buildInfoItem(
                              'Class',
                              studentData!['class']?['name'] ?? 'N/A',
                              Icons.class_),
                          _buildInfoItem(
                              'Stream',
                              studentData!['stream']?['name'] ?? 'N/A',
                              Icons.stream),
                          _buildInfoItem(
                              'Academic Year',
                              studentData!['academic_year']?['year_name'] ??
                                  'N/A',
                              Icons.calendar_month),
                          _buildInfoItem('Status',
                              studentData!['status'] ?? 'N/A', Icons.info),
                          _buildInfoItem(
                              'Admission Date',
                              _formatDate(studentData!['admission_date']),
                              Icons.event),
                          _buildInfoItem(
                              'Boarding Type',
                              studentData!['boarding_type'] ?? 'N/A',
                              Icons.home),
                        ],
                      ),
                    ],
                  ),
                ),
    );
  }
}

