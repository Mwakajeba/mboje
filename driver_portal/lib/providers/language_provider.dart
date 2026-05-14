import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';

/// Kiswahili (sw) default, English (en). Driver portal translations.
class LanguageProvider extends ChangeNotifier {
  String _currentLanguage = 'sw';

  String get currentLanguage => _currentLanguage;

  LanguageProvider() {
    _loadLanguage();
  }

  Future<void> _loadLanguage() async {
    final prefs = await SharedPreferences.getInstance();
    final set = prefs.containsKey('driver_portal_language');
    _currentLanguage = set ? (prefs.getString('driver_portal_language') ?? 'sw') : 'sw';
    if (!set) await prefs.setString('driver_portal_language', 'sw');
    notifyListeners();
  }

  Future<void> setLanguage(String language) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('driver_portal_language', language);
    _currentLanguage = language;
    notifyListeners();
  }
}

class AppTranslations {
  final String language;

  AppTranslations(this.language);

  String get(String key) {
    return _translations[key]?[language] ?? key;
  }

  static const Map<String, Map<String, String>> _translations = {
    'app_name': {'sw': 'FASTTRACK', 'en': 'FASTTRACK'},
    'welcome_back': {'sw': 'Karibu tena', 'en': 'Welcome back'},
    'enter_credentials': {'sw': 'Ingiza taarifa zako kuanza kazi yako.', 'en': 'Enter your credentials to start your shift.'},
    'phone_label': {'sw': 'Simu', 'en': 'Phone'},
    'driver_id_or_phone': {'sw': 'Kitambulisho cha Dereva au Simu', 'en': 'Driver ID or Phone'},
    'driver_id_placeholder': {'sw': 'mfano 0712345678', 'en': 'e.g. 0712345678'},
    'password_label': {'sw': 'Nenosiri', 'en': 'Password'},
    'security_pin': {'sw': 'Nenosiri la Usalama', 'en': 'Security PIN'},
    'forgot_pin': {'sw': 'Umesahau nenosiri?', 'en': 'Forgot password?'},
    'pin_placeholder': {'sw': '••••••', 'en': '••••••'},
    'login_btn': {'sw': 'Ingia', 'en': 'Login'},
    'start_shift': {'sw': 'Ingia', 'en': 'Login'},
    'network_online': {'sw': 'Mtandao wa usafirishaji uko mtandaoni', 'en': 'Logistics network is online'},
    'version': {'sw': 'Toleo 4.22.0 (Thabiti)', 'en': 'Version 4.22.0 (Stable)'},
    'forgot_password': {'sw': 'Umesahau Nenosiri', 'en': 'Forgot Password'},
    'enter_phone_for_otp': {'sw': 'Ingiza namba yako ya simu na tutakutumia msimbo wa OTP.', 'en': 'Enter your phone number and we will send you an OTP code.'},
    'phone_number': {'sw': 'Namba ya Simu', 'en': 'Phone Number'},
    'send_otp': {'sw': 'Tuma OTP', 'en': 'Send OTP'},
    'otp_sent': {'sw': 'OTP imetumwa kwa namba yako.', 'en': 'OTP sent to your phone number.'},
    'enter_otp': {'sw': 'Ingiza msimbo wa OTP (tarakimu 6)', 'en': 'Enter OTP code (6 digits)'},
    'verify': {'sw': 'Thibitisha', 'en': 'Verify'},
    'new_password': {'sw': 'Nenosiri Jipya', 'en': 'New Password'},
    'confirm_password': {'sw': 'Thibitisha Nenosiri', 'en': 'Confirm Password'},
    'reset_password': {'sw': 'Weka Nenosiri Jipya', 'en': 'Reset Password'},
    'back_to_login': {'sw': 'Rudi kwa Kuingia', 'en': 'Back to Login'},
    'home': {'sw': 'Nyumbani', 'en': 'Home'},
    'trips': {'sw': 'Safari', 'en': 'Trips'},
    'wallet': {'sw': 'Pochi', 'en': 'Wallet'},
    'profile': {'sw': 'Wasifu', 'en': 'Profile'},
    'active_trip': {'sw': 'Safari Inayoendelea', 'en': 'Active Trip'},
    'on_trip': {'sw': 'Safari', 'en': 'On Trip'},
    'change': {'sw': 'Badilisha', 'en': 'Change'},
    'trip_id': {'sw': 'Nambari ya Safari', 'en': 'Trip ID'},
    'in_progress': {'sw': 'Inaendelea', 'en': 'In Progress'},
    'from': {'sw': 'Kutoka', 'en': 'From'},
    'to': {'sw': 'Kwenda', 'en': 'To'},
    'view_trip_details': {'sw': 'Angalia Maelezo ya Safari', 'en': 'View Trip Details'},
    'finances': {'sw': 'Fedha', 'en': 'Finances'},
    'allowance': {'sw': 'Posho', 'en': 'Allowance'},
    'eligible': {'sw': 'Unastahiki', 'en': 'Eligible'},
    'paid': {'sw': 'Imelipwa', 'en': 'Paid'},
    'request': {'sw': 'Omba', 'en': 'Request'},
    'cash_advance': {'sw': 'Mapato ya Mapema', 'en': 'Cash Adv'},
    'balance': {'sw': 'Salio', 'en': 'Balance'},
    'issued': {'sw': 'Imetolewa', 'en': 'Issued'},
    'upcoming_trips': {'sw': 'Safari Zinazokuja', 'en': 'Upcoming Trips'},
    'view_all': {'sw': 'Onyesha Zote', 'en': 'View All'},
    'pending_claims': {'sw': 'Madai Yanayosubiri', 'en': 'Pending Claims'},
    'notifications': {'sw': 'Arifa', 'en': 'Notifications'},
    'active': {'sw': 'Zinaendelea', 'en': 'Active'},
    'vehicle': {'sw': 'Gari', 'en': 'Vehicle'},
    'settings': {'sw': 'Mipangilio', 'en': 'Settings'},
    'language': {'sw': 'Lugha', 'en': 'Language'},
    'server_url': {'sw': 'URL ya Seva', 'en': 'Server URL'},
    'change_password': {'sw': 'Badilisha Nenosiri', 'en': 'Change Password'},
    'current_password': {'sw': 'Nenosiri la Sasa', 'en': 'Current Password'},
    'enable_notifications': {'sw': 'Washa Arifa', 'en': 'Enable Notifications'},
    'about': {'sw': 'Kuhusu', 'en': 'About'},
    'logout': {'sw': 'Toka', 'en': 'Logout'},
    'logout_confirm': {'sw': 'Una uhakika unataka kutoka?', 'en': 'Are you sure you want to logout?'},
    'cancel': {'sw': 'Ghairi', 'en': 'Cancel'},
    'save': {'sw': 'Hifadhi', 'en': 'Save'},
    'swahili': {'sw': 'Kiswahili', 'en': 'Swahili'},
    'english': {'sw': 'Kiingereza', 'en': 'English'},
    'app_version': {'sw': 'Toleo la Programu', 'en': 'App Version'},
    'driver_portal': {'sw': 'Driver Portal - SmartAccounting', 'en': 'Driver Portal - SmartAccounting'},
    'coming_soon': {'sw': 'Inakuja hivi karibuni', 'en': 'Coming soon'},
    'trip_info': {'sw': 'Maelezo ya Safari', 'en': 'Trip Info'},
    'on_schedule': {'sw': 'KWA RATIBA', 'en': 'ON SCHEDULE'},
    'client': {'sw': 'Mteja', 'en': 'Client'},
    'cargo': {'sw': 'Mizigo', 'en': 'Cargo'},
    'vehicle_crew': {'sw': 'Gari na Watumishi', 'en': 'Vehicle & Crew'},
    'vehicle_details': {'sw': 'Maelezo ya Gari', 'en': 'Vehicle Details'},
    'assigned_vehicle': {'sw': 'Gari Iliyogawiwa', 'en': 'Assigned Vehicle'},
    'registration_number': {'sw': 'Nambari ya Usajili', 'en': 'Registration No.'},
    'vehicle_name': {'sw': 'Jina la Gari', 'en': 'Vehicle Name'},
    'vehicle_code': {'sw': 'Msimbo wa Gari', 'en': 'Vehicle Code'},
    'truck': {'sw': 'Lori', 'en': 'Truck'},
    'trailer': {'sw': 'Trella', 'en': 'Trailer'},
    'primary_driver': {'sw': 'Dereva Mkuu', 'en': 'Primary Driver'},
    'trip_approaching': {'sw': 'Safari Inakaribia', 'en': 'Trip Approaching'},
    'trip_approaching_msg': {'sw': 'Safari yako inaanza hivi karibuni. Tafadhali jiandae.', 'en': 'Your trip is starting soon. Please get ready.'},
    'no_vehicle_assigned': {'sw': 'Hakuna gari iliyogawiwa', 'en': 'No vehicle assigned'},
    'trip_started_success': {'sw': 'Safari imeanza. Mfumo umesasishwa.', 'en': 'Trip started. System updated.'},
    'trip_completed_success': {'sw': 'Safari imekamilika.', 'en': 'Trip completed.'},
    'operations': {'sw': 'Shughuli', 'en': 'Operations'},
    'start_trip': {'sw': 'ANZA SAFARI', 'en': 'START TRIP'},
    'update_location': {'sw': 'Sasisha Mahali', 'en': 'Update Location'},
    'report_delay': {'sw': 'Ripoti Ucheleweshaji', 'en': 'Report Delay'},
    'request_allowance': {'sw': 'Omba Posho', 'en': 'Request Allowance'},
    'log_fuel': {'sw': 'Andika Mafuta', 'en': 'Log Fuel'},
    'add_expense': {'sw': 'Ongeza Gharama', 'en': 'Add Expense'},
    'report_incident': {'sw': 'Ripoti Tukio', 'en': 'Report Incident'},
    'complete_trip': {'sw': 'MALIZA SAFARI', 'en': 'COMPLETE TRIP'},
    'confirm_trip_start': {'sw': 'Thibitisha Kuanza Safari', 'en': 'Confirm Trip Start'},
    'current_log_time': {'sw': 'Muda wa Sasa', 'en': 'Current Log Time'},
    'starting_point': {'sw': 'Mahali pa Kuanzia', 'en': 'Starting Point'},
    'gps_notice': {'sw': 'Kuanza safari, mahali pako pa GPS patafuatiliwa na kurekodiwa kwa usalama na ripoti kwa wakati halisi.', 'en': 'By starting this trip, your GPS location will be tracked and logged for safety and reporting purposes in real-time.'},
    'confirm_and_start': {'sw': 'Thibitisha na Anza', 'en': 'Confirm & Start'},
    'update_location_title': {'sw': 'Sasisha Mahali', 'en': 'Update Location'},
    'optional_comments': {'sw': 'Maoni (Si lazima)', 'en': 'Optional Comments'},
    'character_limit': {'sw': 'Kikomo: herufi 250', 'en': 'Character limit: 250'},
    'quick_updates': {'sw': 'Sasisho za Haraka', 'en': 'Quick Updates'},
    'submit_update': {'sw': 'Tuma Sasisho', 'en': 'Submit Update'},
    'current_accuracy': {'sw': 'Usahihi wa sasa: +/- mita 5', 'en': 'Current accuracy: +/- 5 meters'},
    'gps_status_captured': {'sw': 'Hali ya GPS: Imepakwa', 'en': 'GPS Status: Captured'},
    'gps_validated': {'sw': 'Imethibitishwa kupitia Satelaiti', 'en': 'Validated via Satellite'},
    'current_position': {'sw': 'Mahali hapa', 'en': 'Current Position'},
    'start_location': {'sw': 'Mahali ulipoanza safari', 'en': 'Where you started the trip'},
    'current_location_live': {'sw': 'Mahali ulipo sasa (Live GPS)', 'en': 'Your current location (Live GPS)'},
    'view_on_map': {'sw': 'Angalia ramani', 'en': 'View on map'},
    'live': {'sw': 'Live', 'en': 'Live'},
    'construction': {'sw': 'Ujenzi', 'en': 'Construction'},
    'heavy_traffic': {'sw': 'Mgongo Mkali', 'en': 'Heavy Traffic'},
    'rest_stop': {'sw': 'Kituo cha Kupumzika', 'en': 'Rest Stop'},
  };
}
