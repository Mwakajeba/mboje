import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../services/parent_api_service.dart';

class LibraryPage extends StatefulWidget {
  final int studentId;
  const LibraryPage({super.key, required this.studentId});

  @override
  State<LibraryPage> createState() => _LibraryPageState();
}

class _LibraryPageState extends State<LibraryPage> {
  List<dynamic> materials = [];
  bool isLoading = true;
  String? selectedType;
  int? selectedSubjectId;

  @override
  void initState() {
    super.initState();
    _loadMaterials();
  }

  Future<void> _loadMaterials() async {
    setState(() {
      isLoading = true;
    });

    try {
      final data = await ParentApiService.getLibraryMaterials(
        widget.studentId,
        type: selectedType,
        subjectId: selectedSubjectId,
      );
      if (mounted) {
        setState(() {
          materials = data ?? [];
          isLoading = false;
        });
      }
    } catch (e) {
      print('Error loading library materials: $e');
      if (mounted) {
        setState(() {
          isLoading = false;
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Maktaba ya Shule'),
        flexibleSpace: Container(
          decoration: BoxDecoration(
            gradient: LinearGradient(
              colors: [Colors.purple.shade700, Colors.purple.shade500],
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
            ),
          ),
        ),
        elevation: 0,
      ),
      body: Column(
        children: [
          // Filters
          Container(
            padding: const EdgeInsets.all(16),
            color: Colors.grey.shade50,
            child: Row(
              children: [
                Expanded(
                  child: DropdownButtonFormField<String>(
                    value: selectedType,
                    decoration: InputDecoration(
                      labelText: 'Aina',
                      border: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(12),
                      ),
                      filled: true,
                      fillColor: Colors.white,
                    ),
                    items: const [
                      DropdownMenuItem(value: null, child: Text('Aina Zote')),
                      DropdownMenuItem(value: 'pdf_book', child: Text('Vitabu vya PDF')),
                      DropdownMenuItem(value: 'notes', child: Text('Maelezo')),
                      DropdownMenuItem(value: 'past_paper', child: Text('Mitihani ya Zamani')),
                      DropdownMenuItem(value: 'assignment', child: Text('Kazi za Nyumbani')),
                    ],
                    onChanged: (value) {
                      setState(() {
                        selectedType = value;
                      });
                      _loadMaterials();
                    },
                  ),
                ),
                const SizedBox(width: 12),
                IconButton(
                  icon: const Icon(Icons.refresh),
                  onPressed: () {
                    setState(() {
                      selectedType = null;
                      selectedSubjectId = null;
                    });
                    _loadMaterials();
                  },
                  tooltip: 'Safisha',
                ),
              ],
            ),
          ),

          // Materials List
          Expanded(
            child: isLoading
                ? const Center(child: CircularProgressIndicator())
                : materials.isEmpty
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
                        padding: const EdgeInsets.all(16),
                        itemCount: materials.length,
                        itemBuilder: (context, index) {
                          final material = materials[index];
                          return _buildMaterialCard(material);
                        },
                      ),
          ),
        ],
      ),
    );
  }

  Widget _buildMaterialCard(Map<String, dynamic> material) {
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
            if (material['file_url'] != null) {
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
                    color: color.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Icon(icon, color: color, size: 24),
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        material['title'] ?? 'Untitled',
                        style: const TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                      if (material['subject_name'] != null && material['subject_name'] != 'All Subjects')
                        Text(
                          material['subject_name'],
                          style: TextStyle(
                            fontSize: 12,
                            color: Colors.grey.shade600,
                          ),
                        ),
                      if (material['file_size'] != null)
                        Text(
                          material['file_size'],
                          style: TextStyle(
                            fontSize: 11,
                            color: Colors.grey.shade500,
                          ),
                        ),
                    ],
                  ),
                ),
                Container(
                  padding: const EdgeInsets.all(8),
                  decoration: BoxDecoration(
                    color: Colors.purple.shade50,
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Icon(
                    Icons.download,
                    color: Colors.purple.shade700,
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
}

