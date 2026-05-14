import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:google_fonts/google_fonts.dart';
import '../../providers/language_provider.dart';
import '../settings/settings_screen.dart';

class ProfilePlaceholderScreen extends StatelessWidget {
  const ProfilePlaceholderScreen({super.key});

  static const Color primary = Color(0xFF135BEC);

  @override
  Widget build(BuildContext context) {
    final trans = AppTranslations(Provider.of<LanguageProvider>(context).currentLanguage);
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(Icons.person_outline, size: 64, color: primary.withOpacity(0.5)),
          const SizedBox(height: 16),
          Text(
            trans.get('profile'),
            style: GoogleFonts.manrope(fontSize: 18, fontWeight: FontWeight.w700, color: Colors.black87),
          ),
          const SizedBox(height: 8),
          Text(
            trans.get('coming_soon'),
            style: GoogleFonts.manrope(fontSize: 14, color: Colors.black54),
          ),
          const SizedBox(height: 24),
          FilledButton.icon(
            onPressed: () => Navigator.push(
              context,
              MaterialPageRoute(builder: (_) => const SettingsScreen()),
            ),
            icon: const Icon(Icons.settings, size: 20),
            label: Text(trans.get('settings')),
            style: FilledButton.styleFrom(backgroundColor: primary),
          ),
        ],
      ),
    );
  }
}
