import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:provider/provider.dart';
import '../../providers/language_provider.dart';
import '../../providers/theme_provider.dart';
import '../../services/parent_api_service.dart';
import '../auth/login_screen.dart';
import '../auth/change_password_screen.dart';
import 'privacy_policy_screen.dart';
import 'terms_of_service_screen.dart';

class SettingsPage extends StatefulWidget {
  const SettingsPage({super.key});

  @override
  State<SettingsPage> createState() => _SettingsPageState();
}

class _SettingsPageState extends State<SettingsPage> {
  bool _notificationsEnabled = true;

  @override
  void initState() {
    super.initState();
    _loadSettings();
  }

  Future<void> _loadSettings() async {
    final prefs = await SharedPreferences.getInstance();
    setState(() {
      _notificationsEnabled = prefs.getBool('notifications') ?? true;
    });
  }

  Future<void> _saveLanguage(String language) async {
    final languageProvider = Provider.of<LanguageProvider>(context, listen: false);
    await languageProvider.setLanguage(language);
    
    if (mounted) {
      final trans = AppTranslations(language);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            language == 'sw' 
              ? trans.get('language_changed_sw')
              : trans.get('language_changed_en')
          ),
          duration: const Duration(seconds: 2),
        ),
      );
    }
  }

  Future<void> _saveNotificationSetting(bool value) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setBool('notifications', value);
    setState(() {
      _notificationsEnabled = value;
    });
  }

  Future<void> _saveDarkModeSetting(bool value) async {
    final themeProvider = Provider.of<ThemeProvider>(context, listen: false);
    await themeProvider.setDarkMode(value);
  }

  Future<void> _logout() async {
    await ParentApiService.logout();
    if (mounted) {
      Navigator.of(context).pushAndRemoveUntil(
        MaterialPageRoute(builder: (context) => LoginScreen()),
        (route) => false,
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final languageProvider = Provider.of<LanguageProvider>(context);
    final themeProvider = Provider.of<ThemeProvider>(context);
    final trans = AppTranslations(languageProvider.currentLanguage);
    final isDark = themeProvider.isDarkMode;
    
    return Scaffold(
      backgroundColor: isDark ? const Color(0xFF101115) : Colors.grey[50],
      appBar: AppBar(
        title: Text(
          trans.get('settings'),
          style: const TextStyle(
            fontSize: 22,
            fontWeight: FontWeight.bold,
          ),
        ),
        backgroundColor: isDark ? const Color(0xFF16181F) : Colors.blue.shade700,
        foregroundColor: Colors.white,
        elevation: 0,
      ),
      body: SingleChildScrollView(
        child: Column(
          children: [
            // Header Section
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(20),
              decoration: BoxDecoration(
                gradient: LinearGradient(
                  colors: isDark 
                      ? [const Color(0xFF16181F), const Color(0xFF1A1D24)]
                      : [Colors.blue.shade700, Colors.blue.shade500],
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                ),
              ),
              child: Column(
                children: [
                  const CircleAvatar(
                    radius: 40,
                    backgroundColor: Colors.white,
                    child: Icon(
                      Icons.settings,
                      size: 40,
                      color: Colors.blue,
                    ),
                  ),
                  const SizedBox(height: 12),
                  Text(
                    trans.get('app_settings'),
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 20,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  const SizedBox(height: 5),
                  Text(
                    trans.get('customize_app'),
                    style: const TextStyle(
                      color: Colors.white70,
                      fontSize: 13,
                    ),
                    textAlign: TextAlign.center,
                  ),
                ],
              ),
            ),
            
            const SizedBox(height: 20),

            // Language Section
            _buildSectionCard(
              title: trans.get('language'),
              icon: Icons.language,
              iconColor: Colors.blue,
              children: [
                _buildLanguageOption(
                  trans.get('swahili'),
                  'sw',
                  Icons.flag,
                  languageProvider.currentLanguage,
                ),
                const Divider(height: 1),
                _buildLanguageOption(
                  trans.get('english'),
                  'en',
                  Icons.flag_outlined,
                  languageProvider.currentLanguage,
                ),
              ],
            ),

            // Notifications Section
            _buildSectionCard(
              title: trans.get('notifications'),
              icon: Icons.notifications,
              iconColor: Colors.orange,
              children: [
                SwitchListTile(
                  title: Text(
                    trans.get('enable_notifications'),
                    style: TextStyle(
                      fontSize: 15,
                      fontWeight: FontWeight.w500,
                      color: isDark ? const Color(0xFFE4E5E6) : Colors.black87,
                    ),
                  ),
                  subtitle: Text(
                    trans.get('notifications_desc'),
                    style: TextStyle(
                      fontSize: 12,
                      color: isDark ? Colors.grey.shade400 : Colors.grey,
                    ),
                  ),
                  value: _notificationsEnabled,
                  onChanged: _saveNotificationSetting,
                  activeColor: Colors.blue.shade700,
                ),
              ],
            ),

            // Appearance Section
            _buildSectionCard(
              title: trans.get('appearance'),
              icon: Icons.palette,
              iconColor: Colors.purple,
              children: [
                SwitchListTile(
                  title: Text(
                    trans.get('dark_mode'),
                    style: TextStyle(
                      fontSize: 15,
                      fontWeight: FontWeight.w500,
                      color: isDark ? const Color(0xFFE4E5E6) : Colors.black87,
                    ),
                  ),
                  subtitle: Text(
                    trans.get('dark_mode_desc'),
                    style: TextStyle(
                      fontSize: 12,
                      color: isDark ? Colors.grey.shade400 : Colors.grey,
                    ),
                  ),
                  value: themeProvider.isDarkMode,
                  onChanged: _saveDarkModeSetting,
                  activeColor: Colors.blue.shade700,
                ),
              ],
            ),

            // Security Section
            _buildSectionCard(
              title: trans.get('security') ?? 'Security',
              icon: Icons.security,
              iconColor: Colors.orange,
              children: [
                ListTile(
                  leading: Icon(Icons.lock_outline, color: isDark ? Colors.grey.shade400 : Colors.grey),
                  title: Text(
                    trans.get('change_password') ?? 'Change Password',
                    style: TextStyle(
                      fontSize: 14,
                      color: isDark ? const Color(0xFFE4E5E6) : Colors.black87,
                    ),
                  ),
                  trailing: Icon(Icons.chevron_right, size: 20, color: isDark ? Colors.grey.shade400 : Colors.grey),
                  onTap: () {
                    Navigator.push(
                      context,
                      MaterialPageRoute(
                        builder: (context) => const ChangePasswordScreen(),
                      ),
                    );
                  },
                ),
              ],
            ),

            // About Section
            _buildSectionCard(
              title: trans.get('about'),
              icon: Icons.info,
              iconColor: Colors.green,
              children: [
                ListTile(
                  leading: Icon(Icons.app_settings_alt, color: isDark ? Colors.grey.shade400 : Colors.grey),
                  title: Text(
                    trans.get('app_version'),
                    style: TextStyle(
                      fontSize: 14,
                      color: isDark ? const Color(0xFFE4E5E6) : Colors.black87,
                    ),
                  ),
                  trailing: Text(
                    'v1.0.0',
                    style: TextStyle(
                      color: isDark ? Colors.grey.shade400 : Colors.grey,
                      fontSize: 14,
                      fontWeight: FontWeight.w500,
                    ),
                  ),
                ),
                Divider(height: 1, color: isDark ? Colors.grey.shade700 : Colors.grey.shade300),
                ListTile(
                  leading: Icon(Icons.privacy_tip, color: isDark ? Colors.grey.shade400 : Colors.grey),
                  title: Text(
                    trans.get('privacy_policy'),
                    style: TextStyle(
                      fontSize: 14,
                      color: isDark ? const Color(0xFFE4E5E6) : Colors.black87,
                    ),
                  ),
                  trailing: Icon(Icons.chevron_right, size: 20, color: isDark ? Colors.grey.shade400 : Colors.grey),
                  onTap: () {
                    Navigator.push(
                      context,
                      MaterialPageRoute(
                        builder: (context) => const PrivacyPolicyScreen(),
                      ),
                    );
                  },
                ),
                Divider(height: 1, color: isDark ? Colors.grey.shade700 : Colors.grey.shade300),
                ListTile(
                  leading: Icon(Icons.description, color: isDark ? Colors.grey.shade400 : Colors.grey),
                  title: Text(
                    trans.get('terms_of_service'),
                    style: TextStyle(
                      fontSize: 14,
                      color: isDark ? const Color(0xFFE4E5E6) : Colors.black87,
                    ),
                  ),
                  trailing: Icon(Icons.chevron_right, size: 20, color: isDark ? Colors.grey.shade400 : Colors.grey),
                  onTap: () {
                    Navigator.push(
                      context,
                      MaterialPageRoute(
                        builder: (context) => const TermsOfServiceScreen(),
                      ),
                    );
                  },
                ),
              ],
            ),

            const SizedBox(height: 20),

            // Logout Button
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16),
              child: ElevatedButton(
                onPressed: () {
                  showDialog(
                    context: context,
                    builder: (context) => AlertDialog(
                      title: Text(trans.get('logout')),
                      content: Text(
                        trans.get('logout_confirm'),
                      ),
                      actions: [
                        TextButton(
                          onPressed: () => Navigator.pop(context),
                          child: Text(trans.get('no')),
                        ),
                        TextButton(
                          onPressed: () {
                            Navigator.pop(context);
                            _logout();
                          },
                          child: Text(
                            trans.get('yes'),
                            style: const TextStyle(color: Colors.red),
                          ),
                        ),
                      ],
                    ),
                  );
                },
                style: ElevatedButton.styleFrom(
                  backgroundColor: Colors.red.shade400,
                  foregroundColor: Colors.white,
                  padding: const EdgeInsets.symmetric(vertical: 14),
                  minimumSize: const Size(double.infinity, 50),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(12),
                  ),
                ),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    const Icon(Icons.logout),
                    const SizedBox(width: 8),
                    Text(
                      trans.get('logout'),
                      style: const TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                  ],
                ),
              ),
            ),

            const SizedBox(height: 30),
          ],
        ),
      ),
    );
  }

  Widget _buildSectionCard({
    required String title,
    required IconData icon,
    required Color iconColor,
    required List<Widget> children,
  }) {
    final themeProvider = Provider.of<ThemeProvider>(context);
    final isDark = themeProvider.isDarkMode;
    
    return Container(
      margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
      decoration: BoxDecoration(
        color: isDark ? const Color(0xFF16181F) : Colors.white,
        borderRadius: BorderRadius.circular(12),
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
          Padding(
            padding: const EdgeInsets.all(16),
            child: Row(
              children: [
                Container(
                  padding: const EdgeInsets.all(8),
                  decoration: BoxDecoration(
                    color: iconColor.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Icon(icon, color: iconColor, size: 22),
                ),
                const SizedBox(width: 12),
                Text(
                  title,
                  style: TextStyle(
                    fontSize: 17,
                    fontWeight: FontWeight.bold,
                    color: isDark ? const Color(0xFFE4E5E6) : Colors.black87,
                  ),
                ),
              ],
            ),
          ),
          const Divider(height: 1),
          ...children,
        ],
      ),
    );
  }

  Widget _buildLanguageOption(String label, String code, IconData icon, String currentLanguage) {
    final isSelected = currentLanguage == code;
    
    return ListTile(
      leading: Icon(
        icon,
        color: isSelected ? Colors.blue.shade700 : Colors.grey,
      ),
      title: Text(
        label,
        style: TextStyle(
          fontSize: 15,
          fontWeight: isSelected ? FontWeight.w600 : FontWeight.w500,
          color: isSelected 
              ? Colors.blue.shade700 
              : (Provider.of<ThemeProvider>(context).isDarkMode 
                  ? const Color(0xFFE4E5E6) 
                  : Colors.black87),
        ),
      ),
      trailing: isSelected
          ? Icon(Icons.check_circle, color: Colors.blue.shade700, size: 24)
          : const Icon(Icons.circle_outlined, color: Colors.grey, size: 24),
      onTap: () => _saveLanguage(code),
    );
  }
}

