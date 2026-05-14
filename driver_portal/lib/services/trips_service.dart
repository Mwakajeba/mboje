import 'dart:async';
import 'package:flutter/foundation.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'dart:convert';
import 'api_service.dart';
import 'notification_service.dart';

class TripsService {
  static final TripsService _instance = TripsService._internal();
  factory TripsService() => _instance;
  TripsService._internal();

  final ApiService _api = ApiService();
  final NotificationService _notificationService = NotificationService();
  
  Timer? _periodicCheckTimer;
  List<String> _notifiedTripIds = [];
  
  static const String _notifiedTripsKey = 'notified_trip_ids';

  Future<void> initialize() async {
    await _loadNotifiedTrips();
    await _notificationService.initialize();
  }

  Future<void> _loadNotifiedTrips() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final stored = prefs.getStringList(_notifiedTripsKey);
      if (stored != null) {
        _notifiedTripIds = stored;
      }
    } catch (e) {
      debugPrint('Error loading notified trips: $e');
    }
  }

  Future<void> _saveNotifiedTrips() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.setStringList(_notifiedTripsKey, _notifiedTripIds);
    } catch (e) {
      debugPrint('Error saving notified trips: $e');
    }
  }

  Future<void> _addNotifiedTrip(String tripId) async {
    if (!_notifiedTripIds.contains(tripId)) {
      _notifiedTripIds.add(tripId);
      // Keep only last 50 trip IDs to avoid memory issues
      if (_notifiedTripIds.length > 50) {
        _notifiedTripIds = _notifiedTripIds.sublist(_notifiedTripIds.length - 50);
      }
      await _saveNotifiedTrips();
    }
  }

  bool _hasBeenNotified(String tripId) {
    return _notifiedTripIds.contains(tripId);
  }

  /// Fetch a single trip by ID (e.g. after starting trip to refresh status).
  Future<Map<String, dynamic>?> fetchTripById(String tripId) async {
    try {
      final response = await _api.get('/driver/trips/$tripId');
      final data = response.data;
      if (data is Map && data['success'] == true && data['data'] != null) {
        return Map<String, dynamic>.from(data['data'] as Map);
      }
      return null;
    } catch (e) {
      debugPrint('Error fetching trip by id: $e');
      return null;
    }
  }

  /// Fetch upcoming trips for the current driver
  Future<Map<String, dynamic>> fetchUpcomingTrips() async {
    try {
      final response = await _api.get('/driver/trips/upcoming');
      final data = response.data;
      
      if (data is Map && data['success'] == true) {
        return {
          'success': true,
          'trips': data['data'] ?? [],
        };
      }
      
      return {
        'success': false,
        'message': data is Map ? data['message'] ?? 'Failed to fetch trips' : 'Failed to fetch trips',
        'trips': [],
      };
    } catch (e) {
      debugPrint('Error fetching upcoming trips: $e');
      return {
        'success': false,
        'message': e.toString(),
        'trips': [],
      };
    }
  }

  /// Fetch all trips (active and upcoming)
  Future<Map<String, dynamic>> fetchAllTrips() async {
    try {
      final response = await _api.get('/driver/trips');
      final data = response.data;
      
      if (data is Map && data['success'] == true) {
        return {
          'success': true,
          'trips': data['data'] ?? [],
        };
      }
      
      return {
        'success': false,
        'message': data is Map ? data['message'] ?? 'Failed to fetch trips' : 'Failed to fetch trips',
        'trips': [],
      };
    } catch (e) {
      debugPrint('Error fetching trips: $e');
      return {
        'success': false,
        'message': e.toString(),
        'trips': [],
      };
    }
  }

  /// Check for new trips and send notifications
  Future<void> checkForNewTrips() async {
    try {
      final result = await fetchUpcomingTrips();
      
      if (result['success'] == true) {
        final trips = result['trips'] as List;
        
        for (var trip in trips) {
          if (trip is! Map) continue;
          
          final tripId = trip['id']?.toString() ?? '';
          final status = trip['status']?.toString() ?? '';
          
          // Only notify for pending/scheduled trips that haven't been notified yet
          if (tripId.isNotEmpty && 
              (status == 'pending' || status == 'scheduled') && 
              !_hasBeenNotified(tripId)) {
            
            await _sendTripNotification(Map<String, dynamic>.from(trip));
            await _addNotifiedTrip(tripId);
          }
        }
      }
    } catch (e) {
      debugPrint('Error checking for new trips: $e');
    }
  }

  Future<void> _sendTripNotification(Map<String, dynamic> trip) async {
    try {
      final tripId = trip['id']?.toString() ?? '';
      final tripNumber = trip['trip_number']?.toString() ?? 'N/A';
      final origin = trip['origin_location']?.toString() ?? 'Unknown';
      final destination = trip['destination_location']?.toString() ?? 'Unknown';
      final startDateStr = trip['planned_start_date']?.toString() ?? '';
      final cargoDescription = trip['cargo_description']?.toString();
      
      String formattedStartDate = 'Not scheduled';
      if (startDateStr.isNotEmpty) {
        try {
          final startDate = DateTime.parse(startDateStr);
          formattedStartDate = _formatDateTime(startDate);
        } catch (e) {
          formattedStartDate = startDateStr;
        }
      }

      await _notificationService.showTripNotification(
        tripId: tripId,
        tripNumber: tripNumber,
        origin: origin,
        destination: destination,
        startDate: formattedStartDate,
        cargoDescription: cargoDescription,
      );
    } catch (e) {
      debugPrint('Error sending trip notification: $e');
    }
  }

  String _formatDateTime(DateTime date) {
    final now = DateTime.now();
    final difference = date.difference(now);

    if (difference.inDays == 0) {
      if (difference.inHours >= 0 && difference.inHours < 24) {
        return 'Today at ${_formatTime(date)}';
      }
    } else if (difference.inDays == 1) {
      return 'Tomorrow at ${_formatTime(date)}';
    } else if (difference.inDays > 1 && difference.inDays < 7) {
      return '${_getWeekday(date.weekday)} at ${_formatTime(date)}';
    }

    return '${date.day}/${date.month}/${date.year} at ${_formatTime(date)}';
  }

  String _formatTime(DateTime date) {
    final hour = date.hour.toString().padLeft(2, '0');
    final minute = date.minute.toString().padLeft(2, '0');
    return '$hour:$minute';
  }

  String _getWeekday(int weekday) {
    const days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
    return days[weekday - 1];
  }

  /// Start periodic checking for new trips (every 5 minutes)
  void startPeriodicCheck({Duration interval = const Duration(minutes: 5)}) {
    stopPeriodicCheck(); // Stop any existing timer
    
    // Check immediately
    checkForNewTrips();
    
    // Then check periodically
    _periodicCheckTimer = Timer.periodic(interval, (_) {
      checkForNewTrips();
    });
  }

  /// Stop periodic checking
  void stopPeriodicCheck() {
    _periodicCheckTimer?.cancel();
    _periodicCheckTimer = null;
  }

  /// Send a reminder notification for upcoming trip
  Future<void> sendTripReminder(Map<String, dynamic> trip) async {
    try {
      final tripId = trip['id']?.toString() ?? '';
      final tripNumber = trip['trip_number']?.toString() ?? 'N/A';
      final origin = trip['origin_location']?.toString() ?? 'Unknown';
      final destination = trip['destination_location']?.toString() ?? 'Unknown';
      final startDateStr = trip['planned_start_date']?.toString();

      if (startDateStr != null && startDateStr.isNotEmpty) {
        final startDate = DateTime.parse(startDateStr);
        
        await _notificationService.showUpcomingTripReminder(
          tripId: tripId,
          tripNumber: tripNumber,
          origin: origin,
          destination: destination,
          startTime: startDate,
        );
      }
    } catch (e) {
      debugPrint('Error sending trip reminder: $e');
    }
  }

  /// Start a trip (updates system: status → in_progress / dispatched).
  /// Pass [latitude], [longitude] and [locationName] to record where the driver was when they started the trip.
  Future<Map<String, dynamic>> startTrip(
    String tripId, {
    double? odometerStart,
    double? latitude,
    double? longitude,
    String? locationName,
    String? notes,
  }) async {
    try {
      final body = <String, dynamic>{};
      if (odometerStart != null) body['odometer_start'] = odometerStart;
      if (latitude != null) body['location_latitude'] = latitude;
      if (longitude != null) body['location_longitude'] = longitude;
      if (locationName != null && locationName.isNotEmpty) body['location_name'] = locationName;
      if (notes != null && notes.isNotEmpty) body['notes'] = notes;

      final response = await _api.post('/driver/trips/$tripId/start', data: body.isEmpty ? null : body);
      final data = response.data;

      if (data is Map && data['success'] == true) {
        return {'success': true, 'data': data['data'], 'message': data['message'] ?? 'Trip started'};
      }
      return {
        'success': false,
        'message': data is Map ? data['message'] ?? 'Failed to start trip' : 'Failed to start trip',
      };
    } catch (e) {
      debugPrint('Error starting trip: $e');
      return {'success': false, 'message': e.toString()};
    }
  }

  /// Update current location for an active trip (saves to backend for reporting).
  /// [locationName] is the reverse-geocoded address (optional). [notes] optional comment.
  /// Returns success/message for UI feedback.
  Future<Map<String, dynamic>> updateTripLocation(
    String tripId,
    double latitude,
    double longitude, {
    String? locationName,
    String? notes,
  }) async {
    try {
      final data = <String, dynamic>{
        'latitude': latitude,
        'longitude': longitude,
      };
      if (locationName != null && locationName.isNotEmpty) data['location_name'] = locationName;
      if (notes != null && notes.isNotEmpty) data['notes'] = notes;
      final response = await _api.post(
        '/driver/trips/$tripId/update-location',
        data: data,
      );
      final res = response.data;
      if (res is Map && res['success'] == true) {
        return {'success': true, 'message': res['message'] ?? 'Location updated'};
      }
      return {'success': false, 'message': res is Map ? res['message'] ?? 'Failed' : 'Failed'};
    } catch (e) {
      debugPrint('Error updating trip location: $e');
      return {'success': false, 'message': e.toString()};
    }
  }

  /// Report delay on a trip
  Future<Map<String, dynamic>> reportDelay(
    String tripId, {
    required String reason,
    int? estimatedDelayMinutes,
    String? notes,
  }) async {
    try {
      final body = <String, dynamic>{
        'reason': reason,
        if (estimatedDelayMinutes != null) 'estimated_delay_minutes': estimatedDelayMinutes,
        if (notes != null && notes.isNotEmpty) 'notes': notes,
      };
      final response = await _api.post('/driver/trips/$tripId/report-delay', data: body);
      final data = response.data;
      if (data is Map && data['success'] == true) {
        return {'success': true, 'message': data['message'] ?? 'Delay reported'};
      }
      return {'success': false, 'message': data is Map ? data['message'] ?? 'Failed' : 'Failed'};
    } catch (e) {
      debugPrint('Error reporting delay: $e');
      return {'success': false, 'message': e.toString()};
    }
  }

  /// Fetch fuel form options: GL accounts (diesel/petrol) and bank accounts (paid from).
  /// [tripId] optional: when provided, response includes previous_odometer for that trip's vehicle.
  Future<Map<String, dynamic>?> fetchFuelOptions({String? tripId}) async {
    try {
      final path = tripId != null && tripId.isNotEmpty
          ? '/driver/fuel-options?trip_id=$tripId'
          : '/driver/fuel-options';
      final response = await _api.get(path);
      final data = response.data;
      if (data is Map && data['success'] == true && data['data'] != null) {
        return Map<String, dynamic>.from(data['data'] as Map);
      }
      return null;
    } catch (e) {
      debugPrint('Error fetching fuel options: $e');
      return null;
    }
  }

  /// Log fuel for a trip. Optional GL: pass [glAccountId] and [paidFromAccountId] to post to GL like web.
  Future<Map<String, dynamic>> logFuel(
    String tripId, {
    required double litersFilled,
    double? costPerLiter,
    double? totalCost,
    double? odometerReading,
    double? previousOdometer,
    String? fuelStation,
    String? fuelType,
    int? glAccountId,
    int? paidFromAccountId,
    bool fuelCardUsed = false,
    String? fuelCardNumber,
    String? fuelCardType,
    String? receiptNumber,
    String? dateFilled,
    String? timeFilled,
    String? notes,
  }) async {
    try {
      final body = <String, dynamic>{
        'liters_filled': litersFilled,
        if (costPerLiter != null) 'cost_per_liter': costPerLiter,
        if (totalCost != null) 'total_cost': totalCost,
        if (odometerReading != null) 'odometer_reading': odometerReading,
        if (previousOdometer != null) 'previous_odometer': previousOdometer,
        if (fuelStation != null) 'fuel_station': fuelStation,
        if (fuelType != null) 'fuel_type': fuelType,
        if (glAccountId != null) 'gl_account_id': glAccountId,
        if (paidFromAccountId != null) 'paid_from_account_id': paidFromAccountId,
        'fuel_card_used': fuelCardUsed,
        if (fuelCardNumber != null) 'fuel_card_number': fuelCardNumber,
        if (fuelCardType != null) 'fuel_card_type': fuelCardType,
        if (receiptNumber != null) 'receipt_number': receiptNumber,
        if (dateFilled != null) 'date_filled': dateFilled,
        if (timeFilled != null) 'time_filled': timeFilled,
        if (notes != null) 'notes': notes,
      };
      final response = await _api.post('/driver/trips/$tripId/log-fuel', data: body);
      final data = response.data;
      if (data is Map && data['success'] == true) {
        return {'success': true, 'message': data['message'] ?? 'Fuel logged'};
      }
      return {'success': false, 'message': data is Map ? data['message'] ?? 'Failed' : 'Failed'};
    } catch (e) {
      debugPrint('Error logging fuel: $e');
      return {'success': false, 'message': e.toString()};
    }
  }

  /// Add expense to a trip
  Future<Map<String, dynamic>> addExpense(
    String tripId, {
    required double amount,
    required String costType,
    required String description,
    String? dateIncurred,
    String? notes,
  }) async {
    try {
      final body = <String, dynamic>{
        'amount': amount,
        'cost_type': costType,
        'description': description,
        if (dateIncurred != null) 'date_incurred': dateIncurred,
        if (notes != null) 'notes': notes,
      };
      final response = await _api.post('/driver/trips/$tripId/add-expense', data: body);
      final data = response.data;
      if (data is Map && data['success'] == true) {
        return {'success': true, 'message': data['message'] ?? 'Expense added'};
      }
      return {'success': false, 'message': data is Map ? data['message'] ?? 'Failed' : 'Failed'};
    } catch (e) {
      debugPrint('Error adding expense: $e');
      return {'success': false, 'message': e.toString()};
    }
  }

  /// Report incident on a trip
  Future<Map<String, dynamic>> reportIncident(
    String tripId, {
    required String description,
    String? severity,
  }) async {
    try {
      final body = <String, dynamic>{
        'description': description,
        if (severity != null) 'severity': severity,
      };
      final response = await _api.post('/driver/trips/$tripId/report-incident', data: body);
      final data = response.data;
      if (data is Map && data['success'] == true) {
        return {'success': true, 'message': data['message'] ?? 'Incident reported'};
      }
      return {'success': false, 'message': data is Map ? data['message'] ?? 'Failed' : 'Failed'};
    } catch (e) {
      debugPrint('Error reporting incident: $e');
      return {'success': false, 'message': e.toString()};
    }
  }

  /// Complete a trip
  Future<Map<String, dynamic>> completeTrip(
    String tripId, {
    double? odometerEnd,
    String? notes,
  }) async {
    try {
      final body = <String, dynamic>{};
      if (odometerEnd != null) body['odometer_end'] = odometerEnd;
      if (notes != null && notes.isNotEmpty) body['notes'] = notes;

      final response = await _api.post('/driver/trips/$tripId/complete', data: body.isEmpty ? null : body);
      final data = response.data;

      if (data is Map && data['success'] == true) {
        return {'success': true, 'data': data['data'], 'message': data['message'] ?? 'Trip completed'};
      }
      return {
        'success': false,
        'message': data is Map ? data['message'] ?? 'Failed to complete trip' : 'Failed to complete trip',
      };
    } catch (e) {
      debugPrint('Error completing trip: $e');
      return {'success': false, 'message': e.toString()};
    }
  }

  /// Clear all notification history (for testing or logout)
  Future<void> clearNotificationHistory() async {
    _notifiedTripIds.clear();
    await _saveNotifiedTrips();
  }

  void dispose() {
    stopPeriodicCheck();
  }
}
