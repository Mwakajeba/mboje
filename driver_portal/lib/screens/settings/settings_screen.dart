import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../../config/api_config.dart';
import '../../services/api_service.dart';
import '../../services/auth_service.dart';
import '../../providers/language_provider.dart';
import '../auth/login_screen.dart';

const String _prefNotifications = 'driver_portal_notifications_enabled';

class SettingsScreen extends StatefulWidget {
  const SettingsScreen({super.key});

  @override
  State<SettingsScreen> createState() => _SettingsScreenState();
}

class _SettingsScreenState extends State<SettingsScreen> {
  final AuthService _auth = AuthService();
  bool _notificationsEnabled = true;

  @override
  void initState() {
    super.initState();
    _loadNotificationsPref();
  }

  Future<void> _loadNotificationsPref() async {
    final prefs = await SharedPreferences.getInstance();
    setState(() => _notificationsEnabled = prefs.getBool(_prefNotifications) ?? true);
  }

  Future<void> _setNotificationsEnabled(bool v) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setBool(_prefNotifications, v);
    setState(() => _notificationsEnabled = v);
  }

  static const Color _primary = Color(0xFF135BEC);
  static const Color _text = Color(0xFF1A1D21);
  static const Color _textSecondary = Color(0xFF6B7280);

  Future<void> _showServerUrlDialog(AppTranslations trans) async {
    final current = await ApiConfig.getOverrideUrl() ?? ApiConfig.baseUrl;
    final controller = TextEditingController(text: current);
    if (!mounted) return;
    showDialog<void>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text(trans.get('server_url')),
        content: SingleChildScrollView(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                trans.language == 'sw'
                    ? 'Weka URL ya seva (mfano http://192.168.1.5:8000/api)'
                    : 'Set server URL (e.g. http://192.168.1.5:8000/api)',
                style: const TextStyle(fontSize: 12),
              ),
              const SizedBox(height: 12),
              TextField(
                controller: controller,
                decoration: InputDecoration(
                  labelText: 'URL',
                  hintText: 'http://192.168.1.5:8000/api',
                  border: const OutlineInputBorder(),
                ),
                keyboardType: TextInputType.url,
                autocorrect: false,
              ),
            ],
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx),
            child: Text(trans.get('cancel')),
          ),
          FilledButton(
            onPressed: () async {
              final url = controller.text.trim();
              if (url.isEmpty) return;
              await ApiConfig.setOverrideUrl(url);
              ApiService().updateBaseUrl(ApiConfig.baseUrl);
              if (ctx.mounted) Navigator.pop(ctx);
              if (mounted) {
                ScaffoldMessenger.of(context).showSnackBar(
                  SnackBar(content: Text(trans.get('save') + ' – ' + (trans.language == 'sw' ? 'Imefanikiwa' : 'Saved'))),
                );
              }
            },
            child: Text(trans.get('save')),
          ),
        ],
      ),
    );
  }

  Future<void> _showChangePasswordDialog(AppTranslations trans) async {
    final current = TextEditingController();
    final newPass = TextEditingController();
    final confirm = TextEditingController();
    final formKey = GlobalKey<FormState>();
    bool loading = false;

    if (!mounted) return;
    showDialog<void>(
      context: context,
      builder: (ctx) => StatefulBuilder(
        builder: (context, setDialogState) {
          return AlertDialog(
            title: Text(trans.get('change_password')),
            content: SingleChildScrollView(
              child: Form(
                key: formKey,
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    TextFormField(
                      controller: current,
                      obscureText: true,
                      decoration: InputDecoration(
                        labelText: trans.get('current_password'),
                        border: const OutlineInputBorder(),
                      ),
                      validator: (v) => (v == null || v.isEmpty) ? (trans.language == 'sw' ? 'Ingiza nenosiri la sasa' : 'Required') : null,
                    ),
                    const SizedBox(height: 12),
                    TextFormField(
                      controller: newPass,
                      obscureText: true,
                      decoration: InputDecoration(
                        labelText: trans.get('new_password'),
                        border: const OutlineInputBorder(),
                      ),
                      validator: (v) {
                        if (v == null || v.isEmpty) return trans.language == 'sw' ? 'Ingiza nenosiri jipya' : 'Required';
                        if (v.length < 8) return trans.language == 'sw' ? 'Angalau herufi 8' : 'At least 8 characters';
                        return null;
                      },
                    ),
                    const SizedBox(height: 12),
                    TextFormField(
                      controller: confirm,
                      obscureText: true,
                      decoration: InputDecoration(
                        labelText: trans.get('confirm_password'),
                        border: const OutlineInputBorder(),
                      ),
                      validator: (v) => v != newPass.text ? (trans.language == 'sw' ? 'Nenosiri halilingani' : 'Passwords do not match') : null,
                    ),
                  ],
                ),
              ),
            ),
            actions: [
              TextButton(
                onPressed: () => Navigator.pop(ctx),
                child: Text(trans.get('cancel')),
              ),
              FilledButton(
                onPressed: loading
                    ? null
                    : () async {
                        if (!formKey.currentState!.validate()) return;
                        setDialogState(() => loading = true);
                        final res = await _auth.changePassword(
                          currentPassword: current.text,
                          newPassword: newPass.text,
                          confirmPassword: confirm.text,
                        );
                        if (ctx.mounted) {
                          setDialogState(() => loading = false);
                          Navigator.pop(ctx);
                          ScaffoldMessenger.of(context).showSnackBar(
                            SnackBar(
                              content: Text(res['message']?.toString() ?? ''),
                              backgroundColor: res['success'] == true ? Colors.green : Colors.red,
                            ),
                          );
                        }
                      },
                child: loading ? const SizedBox(height: 20, width: 20, child: CircularProgressIndicator(strokeWidth: 2)) : Text(trans.get('save')),
              ),
            ],
          );
        },
      ),
    );
  }

  Future<void> _logout(AppTranslations trans) async {
    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text(trans.get('logout')),
        content: Text(trans.get('logout_confirm')),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: Text(trans.get('cancel')),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(ctx, true),
            child: Text(trans.get('logout')),
          ),
        ],
      ),
    );
    if (ok == true) {
      await _auth.logout();
      if (!mounted) return;
      Navigator.pushAndRemoveUntil(
        context,
        MaterialPageRoute(builder: (_) => const LoginScreen()),
        (_) => false,
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final trans = AppTranslations(Provider.of<LanguageProvider>(context).currentLanguage);

    return Scaffold(
      backgroundColor: const Color(0xFFF2F3F5),
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back),
          onPressed: () => Navigator.pop(context),
          color: _text,
        ),
        title: Text(
          trans.get('settings'),
          style: GoogleFonts.manrope(
            fontSize: 18,
            fontWeight: FontWeight.w700,
            color: _text,
          ),
        ),
      ),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          _sectionTitle(trans.get('language')),
          _tile(
            icon: Icons.language,
            title: trans.language == 'sw' ? trans.get('swahili') : trans.get('english'),
            subtitle: trans.language == 'sw' ? 'Kiswahili' : 'English',
            onTap: () async {
              final lang = trans.language == 'sw' ? 'en' : 'sw';
              await Provider.of<LanguageProvider>(context, listen: false).setLanguage(lang);
              if (mounted) setState(() {});
            },
          ),
          const SizedBox(height: 24),
          _sectionTitle(trans.get('server_url')),
          _tile(
            icon: Icons.dns_outlined,
            title: ApiConfig.baseUrl,
            subtitle: trans.language == 'sw' ? 'Bofya kubadilisha' : 'Tap to change',
            onTap: () => _showServerUrlDialog(trans),
          ),
          const SizedBox(height: 24),
          _sectionTitle(trans.get('change_password')),
          _tile(
            icon: Icons.lock_outline,
            title: trans.get('change_password'),
            onTap: () => _showChangePasswordDialog(trans),
          ),
          const SizedBox(height: 24),
          _sectionTitle(trans.get('notifications')),
          SwitchListTile(
            value: _notificationsEnabled,
            onChanged: (v) => _setNotificationsEnabled(v),
            title: Text(
              trans.get('enable_notifications'),
              style: GoogleFonts.manrope(fontSize: 15, fontWeight: FontWeight.w600, color: _text),
            ),
            activeColor: _primary,
          ),
          const SizedBox(height: 24),
          _sectionTitle(trans.get('about')),
          _tile(
            icon: Icons.info_outline,
            title: trans.get('driver_portal'),
            subtitle: trans.get('version'),
          ),
          const SizedBox(height: 32),
          SizedBox(
            height: 48,
            child: OutlinedButton.icon(
              onPressed: () => _logout(trans),
              icon: const Icon(Icons.logout, size: 20),
              label: Text(
                trans.get('logout'),
                style: GoogleFonts.manrope(fontSize: 15, fontWeight: FontWeight.w700),
              ),
              style: OutlinedButton.styleFrom(
                foregroundColor: Colors.red,
                side: const BorderSide(color: Colors.red),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _sectionTitle(String title) {
    return Padding(
      padding: const EdgeInsets.only(left: 4, bottom: 8),
      child: Text(
        title,
        style: GoogleFonts.manrope(
          fontSize: 12,
          fontWeight: FontWeight.w700,
          color: _textSecondary,
          letterSpacing: 0.5,
        ),
      ),
    );
  }

  Widget _tile({
    required IconData icon,
    required String title,
    String? subtitle,
    VoidCallback? onTap,
  }) {
    return Material(
      color: Colors.white,
      borderRadius: BorderRadius.circular(12),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(12),
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
          child: Row(
            children: [
              Icon(icon, size: 22, color: _primary),
              const SizedBox(width: 14),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      title,
                      style: GoogleFonts.manrope(fontSize: 15, fontWeight: FontWeight.w600, color: _text),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                    if (subtitle != null) ...[
                      const SizedBox(height: 2),
                      Text(
                        subtitle,
                        style: GoogleFonts.manrope(fontSize: 12, color: _textSecondary),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                    ],
                  ],
                ),
              ),
              if (onTap != null) const Icon(Icons.chevron_right, color: _textSecondary),
            ],
          ),
        ),
      ),
    );
  }
}
