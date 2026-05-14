import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';
import 'config/api_config.dart';
import 'services/api_service.dart';
import 'services/notification_service.dart';
import 'services/trips_service.dart';
import 'screens/splash/splash_screen.dart';
import 'providers/language_provider.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await ApiConfig.ensureInitialized();
  ApiService().init();
  
  // Initialize notification service
  await NotificationService().initialize();
  
  // Initialize trips service
  await TripsService().initialize();
  
  runApp(const DriverPortalApp());
}

class DriverPortalApp extends StatelessWidget {
  const DriverPortalApp({super.key});

  static const Color primary = Color(0xFF135BEC);
  static const Color backgroundLight = Color(0xFFF6F6F8);
  static const Color backgroundDark = Color(0xFF101622);

  @override
  Widget build(BuildContext context) {
    return ChangeNotifierProvider(
      create: (_) => LanguageProvider(),
      child: MaterialApp(
        title: 'Driver Portal - FASTTRACK',
        debugShowCheckedModeBanner: false,
        theme: ThemeData(
          useMaterial3: true,
          colorScheme: ColorScheme.fromSeed(
            seedColor: primary,
            primary: primary,
            brightness: Brightness.light,
            surface: backgroundLight,
          ),
          scaffoldBackgroundColor: backgroundLight,
          fontFamily: GoogleFonts.manrope().fontFamily,
        ),
        darkTheme: ThemeData(
          useMaterial3: true,
          colorScheme: ColorScheme.fromSeed(
            seedColor: primary,
            primary: primary,
            brightness: Brightness.dark,
            surface: backgroundDark,
          ),
          scaffoldBackgroundColor: backgroundDark,
          fontFamily: GoogleFonts.manrope().fontFamily,
        ),
        themeMode: ThemeMode.light,
        home: const SplashScreen(),
      ),
    );
  }
}
