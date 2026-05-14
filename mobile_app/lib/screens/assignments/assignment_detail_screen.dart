import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';
import 'dart:io' show Platform;

class AssignmentDetailScreen extends StatelessWidget {
  final Map<String, dynamic> assignment;

  const AssignmentDetailScreen({super.key, required this.assignment});

  @override
  Widget build(BuildContext context) {
    try {
      final title = assignment['title'] ?? assignment['subject'] ?? 'Assignment';
      final subject = assignment['subject'] ?? assignment['subject_name'] ?? 'Unknown Subject';
      final description = assignment['description'] ?? '';
      final instructions = assignment['instructions'] ?? '';
      // Use due_datetime if available (includes time), otherwise use due_date
      final dueDate = assignment['due_datetime'] ?? assignment['due_date'] ?? assignment['dueDate'] ?? '';
      final dueTime = assignment['due_time'] ?? '';
      final type = assignment['type'] ?? '';
      
      // Handle totalMarks - it might come as string or number
      dynamic totalMarksRaw = assignment['total_marks'] ?? 0;
      double totalMarks = 0;
      if (totalMarksRaw is String) {
        totalMarks = double.tryParse(totalMarksRaw) ?? 0;
      } else if (totalMarksRaw is num) {
        totalMarks = totalMarksRaw.toDouble();
      }
      
      // Get attachments - handle both list and null cases
      List<dynamic> attachments = [];
      if (assignment['attachments'] != null) {
        if (assignment['attachments'] is List) {
          attachments = List<dynamic>.from(assignment['attachments']);
        } else if (assignment['attachments'] is Map) {
          // If it's a map, try to get the list from it
          final attData = assignment['attachments'] as Map;
          if (attData['data'] is List) {
            attachments = List<dynamic>.from(attData['data']);
          }
        }
      }
      
      // Debug: Print attachments for troubleshooting
      print('Assignment attachments count: ${attachments.length}');
      if (attachments.isNotEmpty) {
        print('First attachment: ${attachments[0]}');
      }
      
      final marksObtained = assignment['marks_obtained'];
      final score = assignment['score'];
      final grade = assignment['grade'];
      final feedback = assignment['feedback'];

      return Scaffold(
        backgroundColor: Colors.grey.shade50,
        appBar: AppBar(
        title: const Text(
          'Maelezo ya Kazi',
          style: TextStyle(
            fontWeight: FontWeight.bold,
            fontSize: 20,
          ),
        ),
        backgroundColor: Colors.orange.shade600,
        foregroundColor: Colors.white,
        elevation: 0,
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Header Card
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
                            Text(
                              title,
                              style: const TextStyle(
                                color: Colors.white,
                                fontSize: 20,
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                            const SizedBox(height: 4),
                            Text(
                              subject,
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
                ],
              ),
            ),
            const SizedBox(height: 20),

            // Due Date & Time
            _buildInfoCard(
              'Tarehe ya Mwisho',
              _formatDueDate(dueDate, dueTime),
              Icons.calendar_today,
              Colors.blue,
            ),
            const SizedBox(height: 12),

            // Type
            if (type.isNotEmpty)
              _buildInfoCard(
                'Aina ya Kazi',
                _formatType(type),
                Icons.category,
                Colors.purple,
              ),
            const SizedBox(height: 12),

            // Total Marks
            if (totalMarks > 0)
              _buildInfoCard(
                'Alama za Jumla',
                '$totalMarks',
                Icons.star,
                Colors.amber,
              ),
            const SizedBox(height: 12),

            // Marks & Grade (if marked)
            if (marksObtained != null || score != null) ...[
              if (score != null)
                _buildInfoCard(
                  'Alama',
                  score.toString(),
                  Icons.check_circle,
                  Colors.green,
                ),
              const SizedBox(height: 12),
              if (grade != null)
                _buildInfoCard(
                  'Gredi',
                  grade.toString(),
                  Icons.grade,
                  Colors.indigo,
                ),
              const SizedBox(height: 12),
            ],

            // Description
            if (description.isNotEmpty) ...[
              _buildSectionTitle('Maelezo'),
              const SizedBox(height: 8),
              Container(
                width: double.infinity,
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: Colors.grey.shade200),
                ),
                child: Text(
                  description,
                  style: TextStyle(
                    fontSize: 14,
                    color: Colors.grey.shade800,
                    height: 1.5,
                  ),
                ),
              ),
              const SizedBox(height: 20),
            ],

            // Instructions
            if (instructions.isNotEmpty) ...[
              _buildSectionTitle('Maagizo'),
              const SizedBox(height: 8),
              Container(
                width: double.infinity,
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: Colors.grey.shade200),
                ),
                child: Text(
                  instructions,
                  style: TextStyle(
                    fontSize: 14,
                    color: Colors.grey.shade800,
                    height: 1.5,
                  ),
                ),
              ),
              const SizedBox(height: 20),
            ],

            // Feedback (if marked)
            if (feedback != null && feedback.toString().isNotEmpty) ...[
              _buildSectionTitle('Maoni ya Mwalimu'),
              const SizedBox(height: 8),
              Container(
                width: double.infinity,
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: Colors.green.shade50,
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: Colors.green.shade200),
                ),
                child: Text(
                  feedback.toString(),
                  style: TextStyle(
                    fontSize: 14,
                    color: Colors.grey.shade800,
                    height: 1.5,
                  ),
                ),
              ),
              const SizedBox(height: 20),
            ],

            // Attachments
            if (attachments.isNotEmpty) ...[
              _buildSectionTitle('Nakala za Kazi'),
              const SizedBox(height: 12),
              ...attachments.map((attachment) {
                final att = attachment is Map ? attachment : <String, dynamic>{};
                return _buildAttachmentCard(
                  context,
                  att['name'] ?? att['original_name'] ?? 'Document',
                  att['url'] ?? '',
                );
              }),
            ],
          ],
        ),
      ),
    );
    } catch (e) {
      print('Error building assignment detail screen: $e');
      return Scaffold(
        appBar: AppBar(
          title: const Text('Hitilafu'),
          backgroundColor: Colors.red,
          foregroundColor: Colors.white,
        ),
        body: Center(
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              const Icon(Icons.error_outline, size: 64, color: Colors.red),
              const SizedBox(height: 16),
              const Text('Hitilafu: Hatuwezi kuonyesha maelezo ya kazi hii'),
              const SizedBox(height: 8),
              Text('$e', style: const TextStyle(fontSize: 12, color: Colors.grey)),
            ],
          ),
        ),
      );
    }
  }

  Widget _buildInfoCard(String label, String value, IconData icon, Color color) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: Colors.grey.shade200),
      ),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(10),
            decoration: BoxDecoration(
              color: color.withOpacity(0.1),
              borderRadius: BorderRadius.circular(10),
            ),
            child: Icon(icon, color: color, size: 24),
          ),
          const SizedBox(width: 16),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  label,
                  style: TextStyle(
                    fontSize: 12,
                    color: Colors.grey.shade600,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  value,
                  style: TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.bold,
                    color: Colors.grey.shade900,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildSectionTitle(String title) {
    return Text(
      title,
      style: TextStyle(
        fontSize: 18,
        fontWeight: FontWeight.bold,
        color: Colors.grey.shade800,
      ),
    );
  }

  Widget _buildAttachmentCard(BuildContext context, String fileName, String url) {
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: Colors.grey.shade200),
      ),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: Colors.red.shade50,
              borderRadius: BorderRadius.circular(10),
            ),
            child: const Icon(
              Icons.description,
              color: Colors.red,
              size: 28,
            ),
          ),
          const SizedBox(width: 16),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  fileName,
                  style: const TextStyle(
                    fontSize: 14,
                    fontWeight: FontWeight.bold,
                  ),
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                ),
              ],
            ),
          ),
          const SizedBox(width: 12),
          ElevatedButton.icon(
            onPressed: () => _downloadFile(context, url, fileName),
            icon: const Icon(Icons.download, size: 18),
            label: const Text('Pakua'),
            style: ElevatedButton.styleFrom(
              backgroundColor: Colors.orange.shade600,
              foregroundColor: Colors.white,
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
            ),
          ),
        ],
      ),
    );
  }

  Future<void> _downloadFile(BuildContext context, String url, String fileName) async {
    try {
      final uri = Uri.parse(url);
      if (await canLaunchUrl(uri)) {
        await launchUrl(uri, mode: LaunchMode.externalApplication);
      } else {
        if (context.mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text('Haiwezekani kufungua faili: $fileName'),
              backgroundColor: Colors.red,
            ),
          );
        }
      }
    } catch (e) {
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Hitilafu: $e'),
            backgroundColor: Colors.red,
          ),
        );
      }
    }
  }

  String _formatDueDate(String? dueDate, String? dueTime) {
    if (dueDate == null || dueDate.isEmpty) return 'Haijatajwa';
    
    try {
      DateTime date;
      // Handle Y-m-d format (date only) or Y-m-d H:i:s format
      if (dueDate.contains('-')) {
        final parts = dueDate.split(' ');
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
          } else if (dueTime != null && dueTime.isNotEmpty) {
            final timeParts = dueTime.split(':');
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
          date = DateTime.parse(dueDate);
        }
      } else {
        date = DateTime.parse(dueDate);
      }
      
      final dayNames = ['Jumapili', 'Jumatatu', 'Jumanne', 'Jumatano', 'Alhamisi', 'Ijumaa', 'Jumamosi'];
      final monthNames = ['', 'Januari', 'Februari', 'Machi', 'Aprili', 'Mei', 'Juni', 'Julai', 'Agosti', 'Septemba', 'Oktoba', 'Novemba', 'Desemba'];
      
      String formatted = '${dayNames[date.weekday % 7]}, ${date.day} ${monthNames[date.month]} ${date.year}';
      
      // Add time if available
      if (date.hour > 0 || date.minute > 0) {
        final hour12 = date.hour > 12 ? date.hour - 12 : (date.hour == 0 ? 12 : date.hour);
        final amPm = date.hour >= 12 ? 'PM' : 'AM';
        formatted += ' saa ${hour12}:${date.minute.toString().padLeft(2, '0')} $amPm';
      } else if (dueTime != null && dueTime.isNotEmpty) {
        formatted += ' - $dueTime';
      }
      
      return formatted;
    } catch (e) {
      print('Error formatting due date: $dueDate - $e');
      return dueDate;
    }
  }

  String _formatType(String type) {
    final types = {
      'homework': 'Kazi ya Nyumbani',
      'classwork': 'Kazi ya Darasani',
      'project': 'Miradi',
      'revision_task': 'Kazi ya Marudio',
    };
    return types[type.toLowerCase()] ?? type;
  }
}

