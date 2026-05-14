import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../providers/language_provider.dart';

// Subject Card Widget with Gradient (used in Academics Page)
class SubjectCard extends StatelessWidget {
  final String subjectName;
  final Color startColor;
  final Color endColor;
  final int index;
  final VoidCallback onTap;

  const SubjectCard({
    super.key,
    required this.subjectName,
    required this.startColor,
    required this.endColor,
    required this.index,
    required this.onTap,
  });

  IconData _getIconForSubject(String? subjectName) {
    if (subjectName == null) return Icons.book;
    switch (subjectName.toLowerCase()) {
      case 'mathematics':
      case 'hisabati':
        return Icons.calculate;
      case 'english':
      case 'kingereza':
        return Icons.spellcheck;
      case 'kiswahili':
        return Icons.translate;
      case 'science':
      case 'sayansi':
        return Icons.science;
      case 'history':
      case 'historia':
        return Icons.history_edu;
      case 'geography':
      case 'jiografia':
        return Icons.public;
      case 'civics':
      case 'uraia':
        return Icons.gavel;
      case 'physics':
      case 'fizikia':
        return Icons.memory;
      case 'chemistry':
      case 'kemia':
        return Icons.biotech;
      case 'biology':
      case 'baiolojia':
        return Icons.eco;
      default:
        return Icons.book;
    }
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: [startColor, endColor],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
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
          onTap: onTap,
          borderRadius: BorderRadius.circular(16),
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                // Icon
                Container(
                  padding: const EdgeInsets.all(10),
                  decoration: BoxDecoration(
                    color: Colors.white.withOpacity(0.3),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Icon(
                    _getIconForSubject(subjectName),
                    color: Colors.white,
                    size: 28,
                  ),
                ),
                // Subject Name
                Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      subjectName,
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 16,
                        fontWeight: FontWeight.bold,
                      ),
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                    ),
                    const SizedBox(height: 4),
                    Consumer<LanguageProvider>(
                      builder: (context, languageProvider, child) {
                        final trans = AppTranslations(languageProvider.currentLanguage);
                        return Container(
                          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                          decoration: BoxDecoration(
                            color: Colors.white.withOpacity(0.2),
                            borderRadius: BorderRadius.circular(12),
                          ),
                          child: Text(
                            '${trans.get('subject')} ${index + 1}',
                            style: TextStyle(
                              color: Colors.white.withOpacity(0.9),
                              fontSize: 11,
                              fontWeight: FontWeight.w500,
                            ),
                          ),
                        );
                      },
                    ),
                  ],
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

