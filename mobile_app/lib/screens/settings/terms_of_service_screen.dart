import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../providers/language_provider.dart';
import '../../providers/theme_provider.dart';

class TermsOfServiceScreen extends StatelessWidget {
  const TermsOfServiceScreen({super.key});

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
          trans.get('terms_of_service'),
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
                    ? 'MASHARTI YA MATUMIZI'
                    : 'TERMS OF SERVICE',
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
                    ? '1. Kukubali Masharti'
                    : '1. Acceptance of Terms',
                isSwahili
                    ? 'Kwa kutumia programu ya SmartSchool Parent Portal, unakubali kufuata na kuheshimu masharti haya ya matumizi. Ikiwa hukubali masharti haya, tafadhali usitumie programu hii.'
                    : 'By using the SmartSchool Parent Portal application, you agree to follow and respect these terms of service. If you do not agree to these terms, please do not use this application.',
                isDark,
              ),
              const SizedBox(height: 20),
              _buildSection(
                context,
                isSwahili
                    ? '2. Matumizi ya Programu'
                    : '2. Use of Application',
                isSwahili
                    ? 'Programu hii imetengenezwa kwa ajili ya wazazi na walezi wa wanafunzi. Unaruhusiwa kutumia programu hii kwa:\n\n• Kufuatilia maendeleo ya mwanafunzi wako\n• Kuona taarifa za ada na malipo\n• Kuona matokeo ya mitihani na mahudhurio\n• Kuwasiliana na shule\n\nHauruhusiwi kutumia programu hii kwa madhumuni yasiyoidhinishwa.'
                    : 'This application is designed for parents and guardians of students. You are permitted to use this application for:\n\n• Tracking your student\'s progress\n• Viewing fee and payment information\n• Viewing exam results and attendance\n• Communicating with the school\n\nYou are not permitted to use this application for unauthorized purposes.',
                isDark,
              ),
              const SizedBox(height: 20),
              _buildSection(
                context,
                isSwahili
                    ? '3. Akaunti na Usalama'
                    : '3. Account and Security',
                isSwahili
                    ? 'Unawajibika kwa:\n\n• Kuweka siri nambari yako ya akaunti\n• Kuripoti matumizi yoyote yasiyoidhinishwa ya akaunti yako\n• Kutoa taarifa sahihi na za sasa\n• Kufuata sheria na kanuni zote zinazotumika'
                    : 'You are responsible for:\n\n• Keeping your account password secure\n• Reporting any unauthorized use of your account\n• Providing accurate and current information\n• Complying with all applicable laws and regulations',
                isDark,
              ),
              const SizedBox(height: 20),
              _buildSection(
                context,
                isSwahili
                    ? '4. Haki za Umiliki'
                    : '4. Intellectual Property Rights',
                isSwahili
                    ? 'Yaliyomo yote katika programu hii, ikiwa ni pamoja na maandishi, picha, alama za biashara, na programu za kompyuta, ni mali ya SmartSchool na wanaohusika. Hauruhusiwi kunakili, kusambaza, au kutumia yaliyomo bila idhini ya maandishi.'
                    : 'All content in this application, including text, images, trademarks, and computer programs, is the property of SmartSchool and its affiliates. You are not permitted to copy, distribute, or use the content without written permission.',
                isDark,
              ),
              const SizedBox(height: 20),
              _buildSection(
                context,
                isSwahili
                    ? '5. Kikomo cha Jukumu'
                    : '5. Limitation of Liability',
                isSwahili
                    ? 'SmartSchool haitakuwa na jukumu kwa uharibifu wowote unaotokana na matumizi au kutoweza kutumia programu hii, ikiwa ni pamoja na uharibifu wa moja kwa moja, wa aina, au wa matokeo.'
                    : 'SmartSchool shall not be liable for any damage arising from the use or inability to use this application, including direct, indirect, or consequential damages.',
                isDark,
              ),
              const SizedBox(height: 20),
              _buildSection(
                context,
                isSwahili
                    ? '6. Kukatwa kwa Huduma'
                    : '6. Termination of Service',
                isSwahili
                    ? 'Tunaweza kukatiza au kusitisha huduma zako wakati wowote, kwa sababu yoyote, bila kujulisha awali. Unaweza pia kukatiza matumizi yako ya programu wakati wowote.'
                    : 'We may suspend or terminate your service at any time, for any reason, without prior notice. You may also terminate your use of the application at any time.',
                isDark,
              ),
              const SizedBox(height: 20),
              _buildSection(
                context,
                isSwahili
                    ? '7. Mabadiliko ya Masharti'
                    : '7. Changes to Terms',
                isSwahili
                    ? 'Tunaweza kufanya mabadiliko kwa masharti haya wakati wowote. Matumizi yako ya programu baada ya mabadiliko hayo yanamaanisha kuwa unakubali masharti mapya.'
                    : 'We may make changes to these terms at any time. Your continued use of the application after such changes means you accept the new terms.',
                isDark,
              ),
              const SizedBox(height: 20),
              _buildSection(
                context,
                isSwahili
                    ? '8. Sheria Inayotumika'
                    : '8. Governing Law',
                isSwahili
                    ? 'Masharti haya yanatawaliwa na na kufasiriwa kulingana na sheria za Jamhuri ya Muungano wa Tanzania. Utaalamu wowote utasuluhishwa na mahakama za Tanzania.'
                    : 'These terms are governed by and interpreted in accordance with the laws of the United Republic of Tanzania. Any disputes will be resolved by Tanzanian courts.',
                isDark,
              ),
              const SizedBox(height: 20),
              _buildSection(
                context,
                isSwahili
                    ? '9. Wasiliana Nasi'
                    : '9. Contact Us',
                isSwahili
                    ? 'Ikiwa una maswali yoyote kuhusu masharti haya, tafadhali wasiliana nasi kupitia:\n\nBarua pepe: info@smartschool.co.tz\nSimu: +255 XXX XXX XXX'
                    : 'If you have any questions about these terms, please contact us via:\n\nEmail: info@smartschool.co.tz\nPhone: +255 XXX XXX XXX',
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

