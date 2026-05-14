import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';

class LanguageProvider extends ChangeNotifier {
  String _currentLanguage = 'sw'; // Default Kiswahili

  String get currentLanguage => _currentLanguage;

  LanguageProvider() {
    _loadLanguage();
  }

  Future<void> _loadLanguage() async {
    final prefs = await SharedPreferences.getInstance();
    // Check if language has been set before, if not default to Kiswahili
    final hasLanguageSet = prefs.containsKey('language');
    _currentLanguage = hasLanguageSet ? (prefs.getString('language') ?? 'sw') : 'sw';
    if (!hasLanguageSet) {
      // Save default language on first load
      await prefs.setString('language', 'sw');
    }
    notifyListeners();
  }

  Future<void> setLanguage(String language) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('language', language);
    _currentLanguage = language;
    notifyListeners();
  }

  // Translation helper
  String translate(String swahili, String english) {
    return _currentLanguage == 'sw' ? swahili : english;
  }
}

// Translations class for all app strings
class AppTranslations {
  final String language;

  AppTranslations(this.language);

  String get(String key) {
    return _translations[key]?[language] ?? key;
  }

  // All app translations
  static const Map<String, Map<String, String>> _translations = {
    // Common
    'home': {'sw': 'Nyumbani', 'en': 'Home'},
    'back': {'sw': 'Rudi', 'en': 'Back'},
    'search': {'sw': 'Tafuta', 'en': 'Search'},
    'loading': {'sw': 'Inapakia...', 'en': 'Loading...'},
    'error': {'sw': 'Hitilafu', 'en': 'Error'},
    'success': {'sw': 'Imefanikiwa', 'en': 'Success'},
    'cancel': {'sw': 'Ghairi', 'en': 'Cancel'},
    'confirm': {'sw': 'Thibitisha', 'en': 'Confirm'},
    'yes': {'sw': 'Ndio', 'en': 'Yes'},
    'no': {'sw': 'Hapana', 'en': 'No'},
    'save': {'sw': 'Hifadhi', 'en': 'Save'},
    'delete': {'sw': 'Futa', 'en': 'Delete'},
    'edit': {'sw': 'Hariri', 'en': 'Edit'},
    'close': {'sw': 'Funga', 'en': 'Close'},
    
    // Main/Home Screen
    'app_title': {'sw': 'SmartSchool Parent Portal', 'en': 'SmartSchool Parent Portal'},
    'welcome': {'sw': 'Karibu', 'en': 'Welcome'},
    'student_info': {'sw': 'Taarifa za Mwanafunzi', 'en': 'Student Information'},
    'class': {'sw': 'Darasa', 'en': 'Class'},
    'school': {'sw': 'Shule', 'en': 'School'},
    'quick_actions': {'sw': 'Vitendo vya Haraka', 'en': 'Quick Actions'},
    'features': {'sw': 'Huduma', 'en': 'Features'},
    'switch_student': {'sw': 'Badilisha Mwanafunzi', 'en': 'Switch Student'},
    
    // Features
    'subjects': {'sw': 'Masomo', 'en': 'Subjects'},
    'assignments': {'sw': 'Kazi', 'en': 'Assignments'},
    'homework': {'sw': 'Kazi za Nyumbani', 'en': 'Homework'},
    'attendance': {'sw': 'Mahudhurio', 'en': 'Attendance'},
    'fees': {'sw': 'Ada', 'en': 'Fees'},
    'report': {'sw': 'Ripoti', 'en': 'Report'},
    'results': {'sw': 'Matokeo', 'en': 'Results'},
    'events': {'sw': 'Matukio', 'en': 'Events'},
    'messages': {'sw': 'Ujumbe', 'en': 'Messages'},
    'more': {'sw': 'Zaidi', 'en': 'More'},
    'timetable': {'sw': 'Ratiba ya Masomo', 'en': 'Timetable'},
    'school_library': {'sw': 'Maktaba ya Shule', 'en': 'School Library'},
    'services': {'sw': 'Huduma', 'en': 'Services'},
    'exams': {'sw': 'Mitihani', 'en': 'Exams'},
    'no_student_info': {'sw': 'Hakuna taarifa za mwanafunzi', 'en': 'No student information'},
    'no_homework': {'sw': 'Hakuna kazi za nyumbani', 'en': 'No homework'},
    'refresh': {'sw': 'Onyesha Upya', 'en': 'Refresh'},
    'present_short': {'sw': 'Hadir', 'en': 'Present'},
    'notification': {'sw': 'Arifa', 'en': 'Notification'},
    'ok': {'sw': 'Sawa', 'en': 'OK'},
    'created_date': {'sw': 'Imepewa', 'en': 'Created'},
    'due_date_short': {'sw': 'Mwisho', 'en': 'Due'},
    
    // Assignments
    'assignments_title': {'sw': 'Kazi za Shule', 'en': 'School Assignments'},
    'all': {'sw': 'Zote', 'en': 'All'},
    'pending': {'sw': 'Zinasubiri', 'en': 'Pending'},
    'submitted': {'sw': 'Zimewasilishwa', 'en': 'Submitted'},
    'search_assignments': {'sw': 'Tafuta kazi...', 'en': 'Search assignments...'},
    'upcoming_assignments': {'sw': 'Kazi Zinazokuja', 'en': 'Upcoming Assignments'},
    'due_soon': {'sw': 'Muda Unakwisha', 'en': 'Due Soon'},
    'submitted_assignments': {'sw': 'Zimewasilishwa', 'en': 'Submitted'},
    'marked_assignments': {'sw': 'Zimepimwa', 'en': 'Marked'},
    'overdue': {'sw': 'Zimechelewa', 'en': 'Overdue'},
    'due_date': {'sw': 'Tarehe ya Kuwasilisha', 'en': 'Due Date'},
    'subject': {'sw': 'Somo', 'en': 'Subject'},
    'status': {'sw': 'Hali', 'en': 'Status'},
    'description': {'sw': 'Maelezo', 'en': 'Description'},
    'marks': {'sw': 'Alama', 'en': 'Marks'},
    'submitted_on': {'sw': 'Imewasilishwa', 'en': 'Submitted On'},
    
    // Attendance
    'attendance_title': {'sw': 'Mahudhurio', 'en': 'Attendance'},
    'attendance_summary': {'sw': 'Muhtasari wa Mahudhurio', 'en': 'Attendance Summary'},
    'total_days': {'sw': 'Jumla ya Siku', 'en': 'Total Days'},
    'present': {'sw': 'Ahudhuria', 'en': 'Present'},
    'absent': {'sw': 'Hayupo', 'en': 'Absent'},
    'late': {'sw': 'Amechelewa', 'en': 'Late'},
    'percentage': {'sw': 'Asilimia', 'en': 'Percentage'},
    'attendance_records': {'sw': 'Rekodi za Mahudhurio', 'en': 'Attendance Records'},
    'date': {'sw': 'Tarehe', 'en': 'Date'},
    'check_in': {'sw': 'Kuingia', 'en': 'Check In'},
    'check_out': {'sw': 'Kutoka', 'en': 'Check Out'},
    'select_month': {'sw': 'Chagua Mwezi', 'en': 'Select Month'},
    
    // Results
    'results_title': {'sw': 'Matokeo ya Mitihani', 'en': 'Exam Results'},
    'select_exam': {'sw': 'Chagua Mtihani', 'en': 'Select Exam'},
    'choose_exam': {'sw': 'Chagua Mtihani', 'en': 'Choose Exam'},
    'select_exam_prompt': {'sw': 'Chagua Mtihani Ili Kuona Matokeo', 'en': 'Select Exam to View Results'},
    'average': {'sw': 'Wastani', 'en': 'Average'},
    'rank': {'sw': 'Nafasi', 'en': 'Rank'},
    'stream': {'sw': 'Mkondo', 'en': 'Stream'},
    'improving': {'sw': 'Inaendelea', 'en': 'Improving'},
    'subject_marks': {'sw': 'Alama za Masomo', 'en': 'Subject Marks'},
    'teacher': {'sw': 'Mwalimu', 'en': 'Teacher'},
    'remarks': {'sw': 'Maoni', 'en': 'Remarks'},
    'excellent': {'sw': 'Bora', 'en': 'Excellent'},
    'good': {'sw': 'Vizuri', 'en': 'Good'},
    'average_remark': {'sw': 'Wastani', 'en': 'Average'},
    'needs_improvement': {'sw': 'Inahitaji Kuboresha', 'en': 'Needs Improvement'},
    'performance_analysis': {'sw': 'Uchanganuzi wa Nguvu na Udhaifu', 'en': 'Performance Analysis'},
    'strong_subjects': {'sw': 'Masomo Imara', 'en': 'Strong Subjects'},
    'weak_subjects': {'sw': 'Masomo Dhaifu', 'en': 'Weak Subjects'},
    'recommendation': {'sw': 'Pendekezo', 'en': 'Recommendation'},
    'study_recommendation': {'sw': 'Dakika 30 za mazoezi ya kusoma kila siku.', 'en': '30 minutes of reading practice daily.'},
    'total_subjects': {'sw': 'Jumla ya Masomo', 'en': 'Total Subjects'},
    'passed': {'sw': 'Yamepita', 'en': 'Passed'},
    'failed': {'sw': 'Yameshindwa', 'en': 'Failed'},
    'absent_exam': {'sw': 'Hakushiriki', 'en': 'Absent'},
    'no_exam_results': {'sw': 'Hakuna matokeo ya mitihani', 'en': 'No exam results available'},
    'exam_performance': {'sw': 'Utendaji wa Mitihani', 'en': 'Exam Performance'},
    'overall_average': {'sw': 'Wastani wa Jumla', 'en': 'Overall Average'},
    'view_details': {'sw': 'Angalia Maelezo', 'en': 'View Details'},
    'grade': {'sw': 'Daraja', 'en': 'Grade'},
    'points': {'sw': 'Pointi', 'en': 'Points'},
    'division': {'sw': 'Daraja', 'en': 'Division'},
    'position': {'sw': 'Nafasi', 'en': 'Position'},
    'total': {'sw': 'Jumla', 'en': 'Total'},
    'subjects_count': {'sw': 'Masomo', 'en': 'Subjects'},
    
    // Settings
    'settings': {'sw': 'Mipangilio', 'en': 'Settings'},
    'app_settings': {'sw': 'Mipangilio ya Programu', 'en': 'App Settings'},
    'customize_app': {'sw': 'Rekebisha programu kulingana na mahitaji yako', 'en': 'Customize the app to your preferences'},
    'language': {'sw': 'Lugha', 'en': 'Language'},
    'swahili': {'sw': 'Kiswahili', 'en': 'Swahili'},
    'english': {'sw': 'Kiingereza', 'en': 'English'},
    'notifications': {'sw': 'Arifa', 'en': 'Notifications'},
    'enable_notifications': {'sw': 'Washa Arifa', 'en': 'Enable Notifications'},
    'notifications_desc': {'sw': 'Pokea arifa za matukio muhimu', 'en': 'Receive important event notifications'},
    'appearance': {'sw': 'Muonekano', 'en': 'Appearance'},
    'dark_mode': {'sw': 'Hali ya Giza', 'en': 'Dark Mode'},
    'dark_mode_desc': {'sw': 'Tumia mandhari ya giza', 'en': 'Use dark theme'},
    'about': {'sw': 'Kuhusu', 'en': 'About'},
    'app_version': {'sw': 'Toleo la Programu', 'en': 'App Version'},
    'privacy_policy': {'sw': 'Sera ya Faragha', 'en': 'Privacy Policy'},
    'terms_of_service': {'sw': 'Masharti ya Matumizi', 'en': 'Terms of Service'},
    'privacy_coming_soon': {'sw': 'Sera ya Faragha itakuja hivi karibuni', 'en': 'Privacy Policy coming soon'},
    'terms_coming_soon': {'sw': 'Masharti ya Matumizi yatakuja hivi karibuni', 'en': 'Terms of Service coming soon'},
    'logout': {'sw': 'Toka', 'en': 'Logout'},
    'logout_confirm': {'sw': 'Je, una uhakika unataka kutoka?', 'en': 'Are you sure you want to logout?'},
    'logged_out': {'sw': 'Umetoka kikamilifu', 'en': 'Logged out successfully'},
    'language_changed_sw': {'sw': 'Lugha imebadilishwa kuwa Kiswahili', 'en': 'Language changed to Swahili'},
    'language_changed_en': {'sw': 'Lugha imebadilishwa kuwa Kiingereza', 'en': 'Language changed to English'},
    
    // Bottom Navigation
    'nav_home': {'sw': 'Nyumbani', 'en': 'Home'},
    'nav_results': {'sw': 'Matokeo', 'en': 'Results'},
    'nav_messages': {'sw': 'Ujumbe', 'en': 'Messages'},
    'nav_settings': {'sw': 'Mipangilio', 'en': 'Settings'},
    'messages_coming_soon': {'sw': 'Ukurasa wa Ujumbe utakuja hivi karibuni', 'en': 'Messages page coming soon'},
    
    // Fees
    'fees_title': {'sw': 'Ada za Shule', 'en': 'School Fees'},
    'no_fees_data': {'sw': 'Hakuna data ya ada', 'en': 'No fees data available'},
    'payment_summary': {'sw': 'Muhtasari wa Malipo', 'en': 'Payment Summary'},
    'total_fee': {'sw': 'Jumla', 'en': 'Total'},
    'paid': {'sw': 'Imelipwa', 'en': 'Paid'},
    'balance': {'sw': 'Deni', 'en': 'Balance'},
    'payment_progress': {'sw': 'Maendeleo ya Malipo', 'en': 'Payment Progress'},
    'fee_group': {'sw': 'Kundi la Ada', 'en': 'Fee Group'},
    'period': {'sw': 'Kipindi', 'en': 'Period'},
    'payment_history': {'sw': 'Historia ya Malipo', 'en': 'Payment History'},
    'amount': {'sw': 'Kiasi', 'en': 'Amount'},
    'pay_now': {'sw': 'Lipa Sasa', 'en': 'Pay Now'},
    
    // Subjects
    'subjects_title': {'sw': 'Masomo', 'en': 'Subjects'},
    'my_subjects': {'sw': 'Masomo Yako', 'en': 'Your Subjects'},
    'subjects_loading': {'sw': 'Inapakia masomo...', 'en': 'Loading subjects...'},
    'no_subjects': {'sw': 'Hakuna masomo yaliyopatikana', 'en': 'No subjects found'},
    
    // Report
    'report_card': {'sw': 'Ripoti ya Mwanafunzi', 'en': 'Student Report Card'},
    'term': {'sw': 'Muhula', 'en': 'Term'},
    'year': {'sw': 'Mwaka', 'en': 'Year'},
    
    // Months
    'january': {'sw': 'Januari', 'en': 'January'},
    'february': {'sw': 'Februari', 'en': 'February'},
    'march': {'sw': 'Machi', 'en': 'March'},
    'april': {'sw': 'Aprili', 'en': 'April'},
    'may': {'sw': 'Mei', 'en': 'May'},
    'june': {'sw': 'Juni', 'en': 'June'},
    'july': {'sw': 'Julai', 'en': 'July'},
    'august': {'sw': 'Agosti', 'en': 'August'},
    'september': {'sw': 'Septemba', 'en': 'September'},
    'october': {'sw': 'Oktoba', 'en': 'October'},
    'november': {'sw': 'Novemba', 'en': 'November'},
    'december': {'sw': 'Desemba', 'en': 'December'},
  };
}

