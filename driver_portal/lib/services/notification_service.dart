import 'package:flutter/foundation.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';
import 'package:timezone/data/latest_all.dart' as tz;
import 'package:timezone/timezone.dart' as tz;
import 'dart:io' show Platform;

class NotificationService {
  static final NotificationService _instance = NotificationService._internal();
  factory NotificationService() => _instance;
  NotificationService._internal();

  final FlutterLocalNotificationsPlugin _notifications = FlutterLocalNotificationsPlugin();
  bool _initialized = false;

  // Notification channels
  static const String _channelId = 'trip_notifications';
  static const String _channelName = 'Trip Notifications';
  static const String _channelDescription = 'Notifications for upcoming trips and trip updates';

  Future<void> initialize() async {
    if (_initialized) return;

    try {
      // Initialize timezone
      tz.initializeTimeZones();

      // Android initialization
      const androidSettings = AndroidInitializationSettings('@mipmap/ic_launcher');

      // iOS initialization
      final iosSettings = DarwinInitializationSettings(
        requestAlertPermission: true,
        requestBadgePermission: true,
        requestSoundPermission: true,
        onDidReceiveLocalNotification: (id, title, body, payload) async {
          // Handle iOS foreground notification
        },
      );

      final initSettings = InitializationSettings(
        android: androidSettings,
        iOS: iosSettings,
      );

      await _notifications.initialize(
        initSettings,
        onDidReceiveNotificationResponse: _onNotificationTapped,
      );

      // Request permissions
      if (!kIsWeb && Platform.isAndroid) {
        await _requestAndroidPermissions();
      } else if (!kIsWeb && Platform.isIOS) {
        await _requestIOSPermissions();
      }

      _initialized = true;
    } catch (e) {
      debugPrint('Error initializing notifications: $e');
    }
  }

  Future<void> _requestAndroidPermissions() async {
    try {
      final android = _notifications.resolvePlatformSpecificImplementation<
          AndroidFlutterLocalNotificationsPlugin>();
      if (android != null) {
        await android.requestNotificationsPermission();
      }
    } catch (e) {
      debugPrint('Error requesting Android permissions: $e');
    }
  }

  Future<void> _requestIOSPermissions() async {
    try {
      final ios = _notifications.resolvePlatformSpecificImplementation<
          IOSFlutterLocalNotificationsPlugin>();
      if (ios != null) {
        await ios.requestPermissions(
          alert: true,
          badge: true,
          sound: true,
        );
      }
    } catch (e) {
      debugPrint('Error requesting iOS permissions: $e');
    }
  }

  void _onNotificationTapped(NotificationResponse response) {
    // Handle notification tap
    // The payload can contain trip_id to navigate to specific trip
    debugPrint('Notification tapped with payload: ${response.payload}');
    
    // You can use a navigator key or event bus to navigate to trip details
    // For now, we'll just log it
  }

  Future<void> showTripNotification({
    required String tripId,
    required String tripNumber,
    required String origin,
    required String destination,
    required String startDate,
    String? cargoDescription,
  }) async {
    if (!_initialized) {
      await initialize();
    }

    try {
      final id = tripId.hashCode;

      const androidDetails = AndroidNotificationDetails(
        _channelId,
        _channelName,
        channelDescription: _channelDescription,
        importance: Importance.high,
        priority: Priority.high,
        showWhen: true,
        icon: '@mipmap/ic_launcher',
        styleInformation: BigTextStyleInformation(''),
        enableVibration: true,
        playSound: true,
      );

      const iosDetails = DarwinNotificationDetails(
        presentAlert: true,
        presentBadge: true,
        presentSound: true,
      );

      const details = NotificationDetails(
        android: androidDetails,
        iOS: iosDetails,
      );

      final body = '''
📍 From: $origin
🏁 To: $destination
📅 Start: $startDate
${cargoDescription != null ? '📦 Cargo: $cargoDescription' : ''}
'''.trim();

      await _notifications.show(
        id,
        '🚛 New Trip Assigned: #$tripNumber',
        body,
        details,
        payload: tripId,
      );
    } catch (e) {
      debugPrint('Error showing trip notification: $e');
    }
  }

  Future<void> showUpcomingTripReminder({
    required String tripId,
    required String tripNumber,
    required String origin,
    required String destination,
    required DateTime startTime,
  }) async {
    if (!_initialized) {
      await initialize();
    }

    try {
      final id = 'reminder_$tripId'.hashCode;

      const androidDetails = AndroidNotificationDetails(
        _channelId,
        _channelName,
        channelDescription: _channelDescription,
        importance: Importance.high,
        priority: Priority.high,
        icon: '@mipmap/ic_launcher',
        enableVibration: true,
        playSound: true,
      );

      const iosDetails = DarwinNotificationDetails(
        presentAlert: true,
        presentBadge: true,
        presentSound: true,
      );

      const details = NotificationDetails(
        android: androidDetails,
        iOS: iosDetails,
      );

      final hoursUntil = startTime.difference(DateTime.now()).inHours;
      final minutesUntil = startTime.difference(DateTime.now()).inMinutes;

      String timeMessage;
      if (hoursUntil > 24) {
        timeMessage = 'in ${(hoursUntil / 24).round()} days';
      } else if (hoursUntil > 1) {
        timeMessage = 'in $hoursUntil hours';
      } else if (minutesUntil > 0) {
        timeMessage = 'in $minutesUntil minutes';
      } else {
        timeMessage = 'now';
      }

      await _notifications.show(
        id,
        '⏰ Trip Reminder: #$tripNumber',
        'Your trip from $origin to $destination starts $timeMessage',
        details,
        payload: tripId,
      );
    } catch (e) {
      debugPrint('Error showing trip reminder: $e');
    }
  }

  Future<void> showTripUpdateNotification({
    required String tripId,
    required String tripNumber,
    required String updateMessage,
  }) async {
    if (!_initialized) {
      await initialize();
    }

    try {
      final id = 'update_$tripId${DateTime.now().millisecondsSinceEpoch}'.hashCode;

      const androidDetails = AndroidNotificationDetails(
        _channelId,
        _channelName,
        channelDescription: _channelDescription,
        importance: Importance.high,
        priority: Priority.high,
        icon: '@mipmap/ic_launcher',
        enableVibration: true,
        playSound: true,
      );

      const iosDetails = DarwinNotificationDetails(
        presentAlert: true,
        presentBadge: true,
        presentSound: true,
      );

      const details = NotificationDetails(
        android: androidDetails,
        iOS: iosDetails,
      );

      await _notifications.show(
        id,
        '📢 Trip Update: #$tripNumber',
        updateMessage,
        details,
        payload: tripId,
      );
    } catch (e) {
      debugPrint('Error showing trip update notification: $e');
    }
  }

  Future<void> cancelNotification(int id) async {
    await _notifications.cancel(id);
  }

  Future<void> cancelAllNotifications() async {
    await _notifications.cancelAll();
  }

  Future<List<PendingNotificationRequest>> getPendingNotifications() async {
    return await _notifications.pendingNotificationRequests();
  }

  Future<int> getNotificationCount() async {
    final pending = await getPendingNotifications();
    return pending.length;
  }
}
