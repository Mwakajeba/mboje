import 'package:flutter/foundation.dart' show kIsWeb;
import 'package:flutter_local_notifications/flutter_local_notifications.dart';
import 'package:permission_handler/permission_handler.dart';
import 'dart:io';

// Conditionally import Firebase - use stub for web
import 'package:firebase_messaging/firebase_messaging.dart' if (dart.library.html) 'package:mobile_app/services/firebase_stub.dart';

class NotificationService {
  static final NotificationService _instance = NotificationService._internal();
  factory NotificationService() => _instance;
  NotificationService._internal();

  final FlutterLocalNotificationsPlugin _localNotifications = FlutterLocalNotificationsPlugin();
  
  bool _initialized = false;
  String? _fcmToken;
  FirebaseMessaging? _firebaseMessaging;

  String? get fcmToken => _fcmToken;

  Future<void> initialize() async {
    if (_initialized || kIsWeb) return;
    
    try {
      // Initialize Firebase Messaging only on mobile platforms
      _firebaseMessaging = FirebaseMessaging.instance;
    } catch (e) {
      print('Firebase Messaging initialization error: $e');
      // Continue without Firebase - app will still work
      return;
    }

    // Request notification permissions
    await _requestPermissions();

    // Initialize local notifications
    await _initializeLocalNotifications();

    // Get FCM token and set up listeners
    if (_firebaseMessaging != null) {
      try {
        _fcmToken = await _firebaseMessaging!.getToken();
        print('FCM Token: $_fcmToken');

        // Listen for token refresh
        _firebaseMessaging!.onTokenRefresh.listen((newToken) {
          _fcmToken = newToken;
          print('FCM Token refreshed: $newToken');
          // TODO: Send token to backend
        });

        // Handle foreground messages
        if (!kIsWeb) {
          FirebaseMessaging.onMessage.listen(_handleForegroundMessage);

          // Handle background messages (when app is in background)
          FirebaseMessaging.onMessageOpenedApp.listen(_handleBackgroundMessage);
        }

        // Handle notification when app is opened from terminated state
        RemoteMessage? initialMessage = await _firebaseMessaging!.getInitialMessage();
        if (initialMessage != null) {
          _handleBackgroundMessage(initialMessage);
        }
      } catch (e) {
        print('Error setting up Firebase Messaging: $e');
      }
    }

    _initialized = true;
  }

  Future<void> _requestPermissions() async {
    if (kIsWeb || _firebaseMessaging == null) return;
    
    if (Platform.isIOS) {
      try {
        NotificationSettings settings = await _firebaseMessaging!.requestPermission(
          alert: true,
          badge: true,
          sound: true,
          provisional: false,
        );
        
        if (settings.authorizationStatus == AuthorizationStatus.authorized) {
          print('User granted notification permission');
        } else if (settings.authorizationStatus == AuthorizationStatus.provisional) {
          print('User granted provisional notification permission');
        } else {
          print('User declined or has not accepted notification permission');
        }
      } catch (e) {
        print('Error requesting iOS permissions: $e');
      }
    } else if (Platform.isAndroid) {
      // Android 13+ requires notification permission
      if (await Permission.notification.isDenied) {
        await Permission.notification.request();
      }
    }
  }

  Future<void> _initializeLocalNotifications() async {
    const AndroidInitializationSettings androidSettings = AndroidInitializationSettings(
      '@mipmap/ic_launcher',
    );

    const DarwinInitializationSettings iosSettings = DarwinInitializationSettings(
      requestAlertPermission: true,
      requestBadgePermission: true,
      requestSoundPermission: true,
    );

    const InitializationSettings initSettings = InitializationSettings(
      android: androidSettings,
      iOS: iosSettings,
    );

    await _localNotifications.initialize(
      initSettings,
      onDidReceiveNotificationResponse: _onNotificationTapped,
    );

    // Create notification channel for Android
    if (Platform.isAndroid) {
      const AndroidNotificationChannel channel = AndroidNotificationChannel(
        'smart_school_notifications',
        'Smart School Notifications',
        description: 'Notifications for Smart School Parent Portal',
        importance: Importance.high,
        playSound: true,
        enableVibration: true,
      );

      await _localNotifications
          .resolvePlatformSpecificImplementation<AndroidFlutterLocalNotificationsPlugin>()
          ?.createNotificationChannel(channel);
    }
  }

  void _handleForegroundMessage(RemoteMessage message) {
    print('Foreground message received: ${message.notification?.title}');
    
    // Show local notification when app is in foreground
    _showLocalNotification(
      title: message.notification?.title ?? 'New Notification',
      body: message.notification?.body ?? '',
      data: message.data,
    );
  }

  void _handleBackgroundMessage(RemoteMessage message) {
    print('Background message received: ${message.notification?.title}');
    // Handle navigation or other actions when notification is tapped
    // This will be handled by the app's navigation logic
  }

  void _onNotificationTapped(NotificationResponse response) {
    print('Notification tapped: ${response.payload}');
    // Handle navigation based on notification data
    // This will be handled by the app's navigation logic
  }

  Future<void> _showLocalNotification({
    required String title,
    required String body,
    Map<String, dynamic>? data,
  }) async {
    const AndroidNotificationDetails androidDetails = AndroidNotificationDetails(
      'smart_school_notifications',
      'Smart School Notifications',
      importance: Importance.high,
      priority: Priority.high,
      showWhen: true,
      enableVibration: true,
      playSound: true,
    );

    const DarwinNotificationDetails iosDetails = DarwinNotificationDetails(
      presentAlert: true,
      presentBadge: true,
      presentSound: true,
    );

    const NotificationDetails notificationDetails = NotificationDetails(
      android: androidDetails,
      iOS: iosDetails,
    );

    await _localNotifications.show(
      DateTime.now().millisecondsSinceEpoch.remainder(100000),
      title,
      body,
      notificationDetails,
      payload: data?.toString(),
    );
  }

  // Background message handler (must be top-level function)
  static Future<void> backgroundMessageHandler(dynamic message) async {
    try {
      if (message is RemoteMessage) {
        print('Background message handler: ${message.notification?.title}');
        // Handle background message processing
      }
    } catch (e) {
      print('Error in background message handler: $e');
    }
  }
}
