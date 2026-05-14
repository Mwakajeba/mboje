// Stub file for web platform - Firebase Messaging is not available on web
// This file is used when compiling for web to avoid import errors

import 'dart:async';

class FirebaseMessaging {
  static FirebaseMessaging get instance => FirebaseMessaging();
  
  Future<String?> getToken() async => null;
  
  Stream<String> get onTokenRefresh => const Stream.empty();
  
  Future<RemoteMessage?> getInitialMessage() async => null;
  
  Future<NotificationSettings> requestPermission({
    bool alert = false,
    bool badge = false,
    bool sound = false,
    bool provisional = false,
  }) async {
    return NotificationSettings(authorizationStatus: AuthorizationStatus.denied);
  }
  
  // Static methods for message handling
  static Stream<RemoteMessage> get onMessage => const Stream.empty();
  
  static Stream<RemoteMessage> get onMessageOpenedApp => const Stream.empty();
  
  static void onBackgroundMessage(Future<void> Function(RemoteMessage) handler) {
    // No-op on web
  }
}

class RemoteMessage {
  final RemoteNotification? notification;
  final Map<String, dynamic>? data;
  
  RemoteMessage({this.notification, this.data});
}

class RemoteNotification {
  final String? title;
  final String? body;
  
  RemoteNotification({this.title, this.body});
}

class NotificationSettings {
  final AuthorizationStatus authorizationStatus;
  NotificationSettings({required this.authorizationStatus});
}

enum AuthorizationStatus {
  authorized,
  denied,
  notDetermined,
  provisional,
}
