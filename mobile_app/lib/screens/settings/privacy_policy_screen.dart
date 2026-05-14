import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../providers/language_provider.dart';
import '../../providers/theme_provider.dart';

class PrivacyPolicyScreen extends StatelessWidget {
  const PrivacyPolicyScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final languageProvider = Provider.of<LanguageProvider>(context);
    final themeProvider = Provider.of<ThemeProvider>(context);
    final trans = AppTranslations(languageProvider.currentLanguage);
    final isDark = themeProvider.isDarkMode;
    final isSwahili = languageProvider.currentLanguage == 'sw';

    return Scaffold(
      backgroundColor: isDark ? const Color(0xFF101115) : Colors.grey.shade50,
      appBar: AppBar(
        title: Text(
          trans.get('privacy_policy'),
          style: const TextStyle(
            fontSize: 20,
            fontWeight: FontWeight.bold,
          ),
        ),
        backgroundColor: isDark ? const Color(0xFF16181F) : Colors.blue.shade700,
        foregroundColor: Colors.white,
        elevation: 0,
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(20),
        child: Container(
          padding: const EdgeInsets.all(20),
          decoration: BoxDecoration(
            color: isDark ? const Color(0xFF16181F) : Colors.white,
            borderRadius: BorderRadius.circular(16),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withOpacity(isDark ? 0.3 : 0.05),
                blurRadius: 10,
                offset: const Offset(0, 2),
              ),
            ],
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                isSwahili
                    ? 'SERA YA FARAGHA'
                    : 'PRIVACY POLICY',
                style: TextStyle(
                  fontSize: 24,
                  fontWeight: FontWeight.bold,
                  color: isDark ? const Color(0xFFE4E5E6) : Colors.black87,
                ),
              ),
              const SizedBox(height: 20),
              _buildSection(
                context,
                isSwahili
                    ? '1. Utangulizi'
                    : '1. Introduction',
                isSwahili
                    ? 'Sera hii ya Faragha inaeleza jinsi tunavyokusanya, kutumia, na kulinda taarifa zako za kibinafsi unapotumia programu yetu ya SmartSchool Parent Portal. Kwa kutumia programu hii, unakubali masharti na hali ya sera hii.'
                    : 'This Privacy Policy describes how we collect, use, and protect your personal information when you use our SmartSchool Parent Portal application. By using this application, you agree to the terms and conditions of this policy.',
                isDark,
              ),
              const SizedBox(height: 20),
              _buildSection(
                context,
                isSwahili
                    ? '2. Taarifa Tunazokusanya'
                    : '2. Information We Collect',
                isSwahili
                    ? 'Tunakusanya taarifa zifuatazo:\n\n• Taarifa za mzazi (jina, nambari ya simu, barua pepe)\n• Taarifa za mwanafunzi (jina, nambari ya uandikishaji, darasa)\n• Taarifa za malipo na ada\n• Taarifa za mahudhurio na matokeo ya mitihani\n• Taarifa za matumizi ya programu'
                    : 'We collect the following information:\n\n• Parent information (name, phone number, email)\n• Student information (name, admission number, class)\n• Payment and fee information\n• Attendance and exam results information\n• Application usage information',
                isDark,
              ),
              const SizedBox(height: 20),
              _buildSection(
                context,
                isSwahili
                    ? '3. Jinsi Tunavyotumia Taarifa'
                    : '3. How We Use Information',
                isSwahili
                    ? 'Tunatumia taarifa zako kwa:\n\n• Kukupa huduma za programu\n• Kuwasiliana nawe kuhusu mwanafunzi wako\n• Kukusaidia kufuatilia maendeleo ya mwanafunzi\n• Kuboresha huduma zetu\n• Kufuata sheria na kanuni'
                    : 'We use your information to:\n\n• Provide you with application services\n• Communicate with you about your student\n• Help you track student progress\n• Improve our services\n• Comply with laws and regulations',
                isDark,
              ),
              const SizedBox(height: 20),
              _buildSection(
                context,
                isSwahili
                    ? '4. Ulinzi wa Taarifa'
                    : '4. Information Security',
                isSwahili
                    ? 'Tunachukua hatua za usalama za kutosha kulinda taarifa zako za kibinafsi dhidi ya ufikiaji usioidhinishwa, mabadiliko, ufichuli, au uharibifu. Tunatumia teknolojia za kisasa za usalama na mbinu za usalama wa data.'
                    : 'We take appropriate security measures to protect your personal information against unauthorized access, alteration, disclosure, or destruction. We use modern security technologies and data security practices.',
                isDark,
              ),
              const SizedBox(height: 20),
              _buildSection(
                context,
                isSwahili
                    ? '5. Kushirikiana na Watu Wengine'
                    : '5. Sharing with Others',
                isSwahili
                    ? 'Hatutauza au kukodisha taarifa zako za kibinafsi kwa watu wengine. Tunaweza kushiriki taarifa zako tu na:\n\n• Walimu na wafanyakazi wa shule kwa madhumuni ya kielimu\n• Watoa huduma wa teknolojia wanaotumika kwa huduma zetu\n• Mamlaka za kisheria ikiwa inahitajika kisheria'
                    : 'We will not sell or rent your personal information to others. We may share your information only with:\n\n• Teachers and school staff for educational purposes\n• Technology service providers used for our services\n• Legal authorities if legally required',
                isDark,
              ),
              const SizedBox(height: 20),
              _buildSection(
                context,
                isSwahili
                    ? '6. Haki Zako'
                    : '6. Your Rights',
                isSwahili
                    ? 'Una haki ya:\n\n• Kufikia taarifa zako za kibinafsi\n• Kusahihisha taarifa zisizo sahihi\n• Kufuta akaunti yako na taarifa zako\n• Kupinga matumizi fulani ya taarifa zako\n• Kuomba nakala ya taarifa zako'
                    : 'You have the right to:\n\n• Access your personal information\n• Correct inaccurate information\n• Delete your account and information\n• Object to certain uses of your information\n• Request a copy of your information',
                isDark,
              ),
              const SizedBox(height: 20),
              _buildSection(
                context,
                isSwahili
                    ? '7. Mabadiliko ya Sera'
                    : '7. Policy Changes',
                isSwahili
                    ? 'Tunaweza kufanya mabadiliko kwa sera hii wakati wowote. Tutakujulisha kuhusu mabadiliko makubwa kwa kuweka taarifa mpya kwenye programu au kukutumia barua pepe.'
                    : 'We may make changes to this policy at any time. We will notify you of significant changes by posting the new policy in the application or sending you an email.',
                isDark,
              ),
              const SizedBox(height: 20),
              _buildSection(
                context,
                isSwahili
                    ? '8. Wasiliana Nasi'
                    : '8. Contact Us',
                isSwahili
                    ? 'Ikiwa una maswali yoyote kuhusu sera hii ya faragha, tafadhali wasiliana nasi kupitia:\n\nBarua pepe: info@smartschool.co.tz\nSimu: +255 XXX XXX XXX'
                    : 'If you have any questions about this privacy policy, please contact us via:\n\nEmail: info@smartschool.co.tz\nPhone: +255 XXX XXX XXX',
                isDark,
              ),
              const SizedBox(height: 20),
              Text(
                isSwahili
                    ? 'Tarehe ya Uthibitishaji: ${DateTime.now().day}/${DateTime.now().month}/${DateTime.now().year}'
                    : 'Last Updated: ${DateTime.now().day}/${DateTime.now().month}/${DateTime.now().year}',
                style: TextStyle(
                  fontSize: 12,
                  fontStyle: FontStyle.italic,
                  color: isDark ? Colors.grey.shade400 : Colors.grey.shade600,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildSection(BuildContext context, String title, String content, bool isDark) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          title,
          style: TextStyle(
            fontSize: 18,
            fontWeight: FontWeight.bold,
            color: isDark ? const Color(0xFFE4E5E6) : Colors.black87,
          ),
        ),
        const SizedBox(height: 10),
        Text(
          content,
          style: TextStyle(
            fontSize: 14,
            height: 1.6,
            color: isDark ? Colors.grey.shade300 : Colors.black87,
          ),
        ),
      ],
    );
  }
}

