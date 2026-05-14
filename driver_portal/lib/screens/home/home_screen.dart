import 'dart:async';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:audioplayers/audioplayers.dart';
import 'package:geolocator/geolocator.dart';
import '../../services/auth_service.dart';
import '../../services/trips_service.dart';
import '../../providers/language_provider.dart';
import '../auth/login_screen.dart';
import '../settings/settings_screen.dart';
import '../trips/trips_placeholder_screen.dart';
import '../wallet/wallet_placeholder_screen.dart';
import '../profile/profile_placeholder_screen.dart';
import '../trip_details/trip_details_screen.dart';
import '../active_trip_map/active_trip_map_screen.dart';
import '../../services/location_helper.dart';

class DriverHomeScreen extends StatefulWidget {
  const DriverHomeScreen({super.key});

  @override
  State<DriverHomeScreen> createState() => _DriverHomeScreenState();
}

class _DriverHomeScreenState extends State<DriverHomeScreen> {
  final AuthService _auth = AuthService();
  final TripsService _tripsService = TripsService();
  final AudioPlayer _audioPlayer = AudioPlayer();
  int _selectedIndex = 0;
  Map<String, dynamic>? _user;
  List<Map<String, dynamic>> _upcomingTrips = [];
  bool _loadingTrips = false;
  Timer? _countdownTimer;
  Set<String> _alarmPlayedForTrips = {};
  Position? _currentLivePosition;
  String? _currentLocationName;
  Timer? _liveLocationTimer;
  String? _resolvedStartLocationName;
  String? _resolvedStartLocationTripId;

  @override
  void initState() {
    super.initState();
    _loadUser();
    _loadUpcomingTrips();
    
    // Start periodic checking for new trips (every 5 minutes)
    _tripsService.startPeriodicCheck(interval: const Duration(minutes: 5));
    
    // Start countdown timer that updates every minute
    _startCountdownTimer();
  }

  @override
  void dispose() {
    _tripsService.stopPeriodicCheck();
    _countdownTimer?.cancel();
    _liveLocationTimer?.cancel();
    _audioPlayer.dispose();
    super.dispose();
  }

  void _startLiveLocationUpdates() {
    _liveLocationTimer?.cancel();
    void updatePosition() async {
      try {
        final pos = await Geolocator.getCurrentPosition(
          desiredAccuracy: LocationAccuracy.medium,
        );
        final name = await getLocationNameFromCoordinates(pos.latitude, pos.longitude);
        if (mounted) {
          setState(() {
            _currentLivePosition = pos;
            _currentLocationName = name;
          });
          final tripId = _user?['current_trip']?['id']?.toString();
          if (tripId != null && tripId.isNotEmpty) {
            _tripsService.updateTripLocation(
              tripId,
              pos.latitude,
              pos.longitude,
              locationName: name,
            );
          }
        }
      } catch (_) {}
    }
    updatePosition();
    _liveLocationTimer = Timer.periodic(const Duration(seconds: 20), (_) => updatePosition());
  }

  void _stopLiveLocationUpdates() {
    _liveLocationTimer?.cancel();
    _liveLocationTimer = null;
    if (mounted) setState(() {
      _currentLivePosition = null;
      _currentLocationName = null;
    });
  }

  void _startCountdownTimer() {
    _countdownTimer = Timer.periodic(const Duration(minutes: 1), (timer) {
      if (mounted) {
        setState(() {}); // Refresh UI to update countdowns
        _checkAndPlayAlarmForApproachingTrips();
      }
    });
  }

  void _checkAndPlayAlarmForApproachingTrips() {
    final now = DateTime.now();
    for (final trip in _upcomingTrips) {
      final tripId = trip['id']?.toString() ?? '';
      if (_alarmPlayedForTrips.contains(tripId)) continue;
      
      final startStr = trip['planned_start_date']?.toString();
      if (startStr == null || startStr.isEmpty) continue;
      
      try {
        final start = DateTime.parse(startStr);
        if (start.isAfter(now)) {
          final diff = start.difference(now);
          // Play alarm when trip is starting (within 5 minutes)
          if (diff.inMinutes <= 5 && diff.inMinutes >= 0) {
            _playAlarmSound();
            _alarmPlayedForTrips.add(tripId);
            _showTripStartingAlert(trip);
          }
        }
      } catch (_) {}
    }
  }

  Future<void> _playAlarmSound() async {
    try {
      await _audioPlayer.play(AssetSource('sounds/trip_alarm.mp3'));
    } catch (e) {
      debugPrint('Error playing alarm sound: $e');
    }
  }

  void _showTripStartingAlert(Map<String, dynamic> trip) {
    final trans = AppTranslations(Provider.of<LanguageProvider>(context, listen: false).currentLanguage);
    final tripNumber = trip['trip_number']?.toString() ?? '—';
    
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (context) => AlertDialog(
        title: Row(
          children: [
            const Icon(Icons.alarm, color: Colors.red, size: 28),
            const SizedBox(width: 8),
            Expanded(
              child: Text(
                trans.language == 'sw' ? 'Safari Inaanza Sasa!' : 'Trip Starting Now!',
                style: GoogleFonts.manrope(
                  fontSize: 16,
                  fontWeight: FontWeight.w700,
                ),
              ),
            ),
          ],
        ),
        content: Text(
          trans.language == 'sw'
              ? 'Safari #$tripNumber inaanza hivi punde. Tafadhali jiandae!'
              : 'Trip #$tripNumber is starting soon. Please get ready!',
          style: GoogleFonts.manrope(fontSize: 14),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: Text(trans.language == 'sw' ? 'SAWA' : 'OK'),
          ),
          ElevatedButton(
            onPressed: () {
              Navigator.pop(context);
              Navigator.push(
                context,
                MaterialPageRoute(
                  builder: (_) => TripDetailsScreen(trip: trip),
                ),
              );
            },
            child: Text(trans.language == 'sw' ? 'ANGALIA SAFARI' : 'VIEW TRIP'),
          ),
        ],
      ),
    );
  }

  Future<void> _loadUser() async {
    final res = await _auth.me();
    if (mounted && res['success'] == true && res['data'] != null) {
      final newUser = res['data'] as Map<String, dynamic>;
      final prevTripId = _user?['current_trip']?['id']?.toString();
      final newTripId = (newUser['current_trip'] as Map<String, dynamic>?)?['id']?.toString();
      setState(() {
        _user = newUser;
        if (newTripId != prevTripId) {
          _resolvedStartLocationTripId = null;
          _resolvedStartLocationName = null;
        }
      });
      if (newUser['current_trip'] != null) {
        _startLiveLocationUpdates();
        final ct = newUser['current_trip'] as Map<String, dynamic>;
        if (_hasStartLocation(ct) && (ct['start_location_name']?.toString()?.trim() ?? '').isEmpty) {
          _resolveStartLocationNameOnce(ct);
        }
      } else {
        _stopLiveLocationUpdates();
      }
    }
  }

  Future<void> _resolveStartLocationNameOnce(Map<String, dynamic> trip) async {
    final tripId = trip['id']?.toString();
    if (tripId == null || tripId.isEmpty) return;
    if (_resolvedStartLocationTripId == tripId && _resolvedStartLocationName != null) return;
    final lat = trip['start_latitude'];
    final lng = trip['start_longitude'];
    if (lat == null || lng == null) return;
    final la = num.tryParse(lat.toString());
    final lo = num.tryParse(lng.toString());
    if (la == null || lo == null) return;
    final name = await getLocationNameFromCoordinates(la.toDouble(), lo.toDouble());
    if (mounted && _user?['current_trip']?['id']?.toString() == tripId) {
      setState(() {
        _resolvedStartLocationTripId = tripId;
        _resolvedStartLocationName = name;
      });
    }
  }

  Future<void> _loadUpcomingTrips() async {
    if (_loadingTrips) return;

    setState(() => _loadingTrips = true);

    try {
      final result = await _tripsService.fetchUpcomingTrips();
      if (mounted && result['success'] == true) {
        final list = (result['trips'] as List)
            .where((t) => t is Map)
            .map((t) => Map<String, dynamic>.from(t as Map))
            .toList();
        setState(() => _upcomingTrips = list);
        // Alert dereva kama safari inakaribia (kwa dakika 60 zinazofuata)
        if (mounted) _showApproachingTripAlertIfAny(list);
      }
    } catch (e) {
      debugPrint('Error loading upcoming trips: $e');
    } finally {
      if (mounted) {
        setState(() => _loadingTrips = false);
      }
    }
  }

  void _showApproachingTripAlertIfAny(List<Map<String, dynamic>> trips) {
    final now = DateTime.now();
    const thresholdHours = 24; // Alert 1 day (24 hours) before
    for (final trip in trips) {
      final startStr = trip['planned_start_date']?.toString();
      if (startStr == null || startStr.isEmpty) continue;
      try {
        final start = DateTime.parse(startStr);
        if (start.isAfter(now)) {
          final diff = start.difference(now);
          // Show alert if trip is within 24 hours
          if (diff.inHours <= thresholdHours && diff.inHours > 0) {
            _showApproachingAlert(trip);
            return;
          }
        }
      } catch (_) {}
    }
  }

  void _showApproachingAlert(Map<String, dynamic> trip) {
    final trans = AppTranslations(Provider.of<LanguageProvider>(context, listen: false).currentLanguage);
    final tripNumber = trip['trip_number']?.toString() ?? '—';
    final origin = trip['origin_location']?.toString() ?? '—';
    final destination = trip['destination_location']?.toString() ?? '—';
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
        title: Row(
          children: [
            Icon(Icons.notification_important, color: Colors.orange.shade700, size: 28),
            const SizedBox(width: 10),
            Expanded(
              child: Text(
                trans.get('trip_approaching'),
                style: GoogleFonts.manrope(
                  fontSize: 16,
                  fontWeight: FontWeight.w700,
                ),
              ),
            ),
          ],
        ),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              trans.get('trip_approaching_msg'),
              style: GoogleFonts.manrope(
                fontSize: 13,
                color: Colors.black87,
              ),
            ),
            const SizedBox(height: 12),
            Container(
              padding: const EdgeInsets.all(10),
              decoration: BoxDecoration(
                color: primary.withOpacity(0.08),
                borderRadius: BorderRadius.circular(8),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Trip #$tripNumber',
                    style: GoogleFonts.manrope(
                      fontSize: 12,
                      fontWeight: FontWeight.w700,
                      color: primary,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    '$origin → $destination',
                    style: GoogleFonts.manrope(
                      fontSize: 11,
                      color: Colors.black87,
                    ),
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                  ),
                ],
              ),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx),
            child: Text(trans.get('cancel')),
          ),
          FilledButton(
            onPressed: () {
              Navigator.pop(ctx);
              Navigator.push(
                context,
                MaterialPageRoute(
                  builder: (_) => TripDetailsScreen(trip: trip),
                ),
              ).then((_) => _loadUpcomingTrips());
            },
            style: FilledButton.styleFrom(
              backgroundColor: primary,
            ),
            child: Text(
              trans.language == 'sw' ? 'Angalia safari' : 'View trip',
            ),
          ),
        ],
      ),
    );
  }

  void _showUpcomingTripsDialog() {
    showDialog(
      context: context,
      builder: (context) => _UpcomingTripsDialog(
        trips: _upcomingTrips,
        onRefresh: _loadUpcomingTrips,
      ),
    );
  }

  Future<void> _logout() async {
    await _auth.logout();
    if (!mounted) return;
    Navigator.pushAndRemoveUntil(
      context,
      MaterialPageRoute(builder: (_) => const LoginScreen()),
      (_) => false,
    );
  }

  static const Color primary = Color(0xFF135BEC);
  static const Color backgroundLight = Color(0xFFF6F6F8);
  static const Color backgroundDark = Color(0xFF101622);

  @override
  Widget build(BuildContext context) {
    // Rangi ya kawaida kwenye mobile: white/light background
    final trans = AppTranslations(Provider.of<LanguageProvider>(context).currentLanguage);

    final driverName = _user?['name']?.toString() ?? 'Driver';
    final vehicle = _user?['vehicle']?.toString() ?? _user?['employee']?['employee_number']?.toString() ?? '—';
    final currentTrip = _user != null && _user!['current_trip'] != null ? _user!['current_trip'] as Map<String, dynamic>? : null;

    return Scaffold(
      backgroundColor: backgroundLight,
      body: SafeArea(
        child: Column(
          children: [
            // Header
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
              decoration: BoxDecoration(
                color: backgroundLight,
                border: Border(
                  bottom: BorderSide(color: Colors.black12),
                ),
              ),
              child: Row(
                children: [
                  Container(
                    width: 36,
                    height: 36,
                    decoration: BoxDecoration(
                      color: primary.withOpacity(0.2),
                      border: Border.all(color: primary, width: 1.5),
                      shape: BoxShape.circle,
                    ),
                    child: Center(
                      child: Text(
                        driverName.isNotEmpty ? driverName[0].toUpperCase() : 'D',
                        style: GoogleFonts.manrope(
                          fontWeight: FontWeight.w700,
                          color: primary,
                          fontSize: 14,
                        ),
                      ),
                    ),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          driverName,
                          style: GoogleFonts.manrope(
                            fontSize: 14,
                            fontWeight: FontWeight.w700,
                            color: Colors.black87,
                          ),
                        ),
                        Text(
                          '${trans.get('vehicle')}: $vehicle',
                          style: GoogleFonts.manrope(
                            fontSize: 11,
                            color: Colors.black54,
                          ),
                        ),
                      ],
                    ),
                  ),
                  Stack(
                    children: [
                      IconButton(
                        onPressed: _showUpcomingTripsDialog,
                        icon: const Icon(Icons.notifications_outlined, size: 22),
                        color: Colors.black54,
                        style: IconButton.styleFrom(
                          padding: const EdgeInsets.all(6),
                          minimumSize: Size.zero,
                          tapTargetSize: MaterialTapTargetSize.shrinkWrap,
                        ),
                      ),
                      if (_upcomingTrips.isNotEmpty)
                        Positioned(
                          right: 4,
                          top: 4,
                          child: Container(
                            padding: const EdgeInsets.all(4),
                            decoration: BoxDecoration(
                              color: Colors.red,
                              shape: BoxShape.circle,
                              border: Border.all(color: Colors.white, width: 1.5),
                            ),
                            constraints: const BoxConstraints(
                              minWidth: 16,
                              minHeight: 16,
                            ),
                            child: Center(
                              child: Text(
                                _upcomingTrips.length > 9 ? '9+' : '${_upcomingTrips.length}',
                                style: GoogleFonts.manrope(
                                  fontSize: 8,
                                  fontWeight: FontWeight.w700,
                                  color: Colors.white,
                                  height: 1,
                                ),
                                textAlign: TextAlign.center,
                              ),
                            ),
                          ),
                        ),
                    ],
                  ),
                  IconButton(
                    onPressed: () => Navigator.push(
                      context,
                      MaterialPageRoute(builder: (_) => const SettingsScreen()),
                    ),
                    icon: const Icon(Icons.settings_outlined, size: 22),
                    color: Colors.black54,
                    style: IconButton.styleFrom(
                      padding: const EdgeInsets.all(6),
                      minimumSize: Size.zero,
                      tapTargetSize: MaterialTapTargetSize.shrinkWrap,
                    ),
                  ),
                ],
              ),
            ),
            Expanded(
              child: IndexedStack(
                index: _selectedIndex,
                children: [
                  SingleChildScrollView(
                    padding: const EdgeInsets.all(16),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        _statusCard(trans),
                        const SizedBox(height: 16),
                        _sectionTitle(trans.get('active_trip'), Icons.local_shipping, primary),
                        const SizedBox(height: 8),
                        _activeTripCard(trans, currentTrip),
                        const SizedBox(height: 16),
                        _sectionTitle(trans.get('finances'), null, null),
                        const SizedBox(height: 8),
                        _financesGrid(trans),
                        const SizedBox(height: 16),
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Text(
                              trans.get('upcoming_trips'),
                              style: GoogleFonts.manrope(
                                fontSize: 15,
                                fontWeight: FontWeight.w700,
                                color: Colors.black87,
                              ),
                            ),
                            TextButton(
                              onPressed: _showUpcomingTripsDialog,
                              child: Text(
                                trans.get('view_all'),
                                style: GoogleFonts.manrope(
                                  fontSize: 12,
                                  fontWeight: FontWeight.w700,
                                  color: primary,
                                ),
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 8),
                        _upcomingTripsCard(trans),
                        const SizedBox(height: 12),
                        _pendingClaimsCard(trans),
                        const SizedBox(height: 10),
                        _notificationsCard(trans),
                        const SizedBox(height: 80),
                      ],
                    ),
                  ),
                  const TripsPlaceholderScreen(),
                  const WalletPlaceholderScreen(),
                  const ProfilePlaceholderScreen(),
                ],
              ),
            ),
            _bottomNav(trans),
          ],
        ),
      ),
      floatingActionButton: Padding(
        padding: const EdgeInsets.only(bottom: 80),
        child: FloatingActionButton(
          onPressed: () {},
          backgroundColor: primary,
          child: const Icon(Icons.warning_amber_rounded, size: 28),
        ),
      ),
    );
  }

  Widget _statusCard(AppTranslations trans) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: Colors.black12),
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Row(
            children: [
              Container(
                width: 8,
                height: 8,
                decoration: const BoxDecoration(
                  color: Colors.orange,
                  shape: BoxShape.circle,
                ),
              ),
              const SizedBox(width: 8),
              Text(
                trans.get('on_trip'),
                style: GoogleFonts.manrope(
                  fontSize: 12,
                  fontWeight: FontWeight.w600,
                  letterSpacing: 0.5,
                  color: Colors.orange,
                ),
              ),
            ],
          ),
          TextButton(
            onPressed: () {},
            style: TextButton.styleFrom(
              padding: const EdgeInsets.symmetric(horizontal: 8),
              minimumSize: Size.zero,
              tapTargetSize: MaterialTapTargetSize.shrinkWrap,
            ),
            child: Text(
              trans.get('change'),
              style: GoogleFonts.manrope(
                fontSize: 11,
                fontWeight: FontWeight.w700,
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _sectionTitle(String title, IconData? icon, Color? iconColor) {
    return Row(
      children: [
        if (icon != null) ...[
          Icon(icon, size: 18, color: iconColor ?? Colors.black54),
          const SizedBox(width: 6),
        ],
        Text(
          title,
          style: GoogleFonts.manrope(
            fontSize: 15,
            fontWeight: FontWeight.w700,
            color: Colors.black87,
          ),
        ),
      ],
    );
  }

  Widget _activeTripCard(AppTranslations trans, Map<String, dynamic>? currentTrip) {
    final tripNumber = currentTrip?['trip_number']?.toString() ?? '';
    final origin = currentTrip?['origin_location']?.toString() ?? '';
    final destination = currentTrip?['destination_location']?.toString() ?? '';
    final status = currentTrip?['status']?.toString() ?? 'in_progress';
    final cargo = currentTrip?['cargo_description']?.toString();
    final customer = currentTrip != null && currentTrip['customer'] is Map
        ? (currentTrip['customer'] as Map)['name']?.toString()
        : null;
    final actualStart = currentTrip?['actual_start_date']?.toString();
    final plannedEnd = currentTrip?['planned_end_date']?.toString();
    DateTime? startDt;
    DateTime? endDt;
    if (actualStart != null && actualStart.isNotEmpty) {
      try {
        startDt = DateTime.tryParse(actualStart);
      } catch (_) {}
    }
    if (plannedEnd != null && plannedEnd.isNotEmpty) {
      try {
        endDt = DateTime.tryParse(plannedEnd);
      } catch (_) {}
    }
    String progressText = '';
    double? progressValue;
    if (startDt != null && endDt != null && endDt.isAfter(startDt)) {
      final total = endDt.difference(startDt).inMinutes;
      final elapsed = DateTime.now().difference(startDt).inMinutes;
      if (total > 0) {
        progressValue = (elapsed / total).clamp(0.0, 1.0);
        final remaining = (total - elapsed).clamp(0, 999999);
        progressText = trans.language == 'sw'
            ? 'Dakika $remaining zimebaki'
            : '$remaining mins remaining';
      }
    }

    if (currentTrip == null) {
      return Container(
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(
          color: Colors.grey.shade200,
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: Colors.grey.shade300),
        ),
        child: Row(
          children: [
            Icon(Icons.route, size: 28, color: Colors.grey.shade600),
            const SizedBox(width: 12),
            Expanded(
              child: Text(
                trans.language == 'sw' ? 'Hakuna safari inayoendelea' : 'No active trip',
                style: GoogleFonts.manrope(
                  fontSize: 13,
                  fontWeight: FontWeight.w600,
                  color: Colors.black54,
                ),
              ),
            ),
          ],
        ),
      );
    }

    return Container(
      decoration: BoxDecoration(
        color: primary,
        borderRadius: BorderRadius.circular(12),
        boxShadow: [
          BoxShadow(
            color: primary.withOpacity(0.3),
            blurRadius: 16,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Padding(
        padding: const EdgeInsets.all(14),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      trans.get('trip_id'),
                      style: GoogleFonts.manrope(
                        fontSize: 9,
                        fontWeight: FontWeight.w500,
                        letterSpacing: 0.5,
                        color: Colors.white.withOpacity(0.8),
                      ),
                    ),
                    Text(
                      tripNumber.isNotEmpty ? '#$tripNumber' : '—',
                      style: GoogleFonts.manrope(
                        fontSize: 16,
                        fontWeight: FontWeight.w700,
                        color: Colors.white,
                      ),
                    ),
                  ],
                ),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                  decoration: BoxDecoration(
                    color: Colors.white.withOpacity(0.2),
                    borderRadius: BorderRadius.circular(999),
                  ),
                  child: Text(
                    status == 'in_progress' ? trans.get('in_progress') : status,
                    style: GoogleFonts.manrope(
                      fontSize: 10,
                      fontWeight: FontWeight.w700,
                      color: Colors.white,
                    ),
                  ),
                ),
              ],
            ),
            if (customer != null && customer.isNotEmpty) ...[
              const SizedBox(height: 6),
              Text(
                trans.get('client') + ': $customer',
                style: GoogleFonts.manrope(
                  fontSize: 10,
                  color: Colors.white70,
                ),
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
              ),
            ],
            if (cargo != null && cargo.isNotEmpty) ...[
              const SizedBox(height: 2),
              Text(
                trans.get('cargo') + ': $cargo',
                style: GoogleFonts.manrope(
                  fontSize: 10,
                  color: Colors.white70,
                ),
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
              ),
            ],
            const SizedBox(height: 10),
            Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Icon(Icons.location_on, size: 16, color: Colors.white70),
                const SizedBox(width: 8),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        trans.get('from'),
                        style: GoogleFonts.manrope(
                          fontSize: 9,
                          color: Colors.white70,
                        ),
                      ),
                      Text(
                        origin.isNotEmpty ? origin : '—',
                        style: GoogleFonts.manrope(
                          fontSize: 12,
                          fontWeight: FontWeight.w600,
                          color: Colors.white,
                        ),
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                      ),
                    ],
                  ),
                ),
              ],
            ),
            const SizedBox(height: 8),
            Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Icon(Icons.flag, size: 16, color: Colors.white70),
                const SizedBox(width: 8),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        trans.get('to'),
                        style: GoogleFonts.manrope(
                          fontSize: 9,
                          color: Colors.white70,
                        ),
                      ),
                      Text(
                        destination.isNotEmpty ? destination : '—',
                        style: GoogleFonts.manrope(
                          fontSize: 12,
                          fontWeight: FontWeight.w600,
                          color: Colors.white,
                        ),
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                      ),
                    ],
                  ),
                ),
              ],
            ),
            if (_hasStartLocation(currentTrip)) ...[
              const SizedBox(height: 8),
              Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Icon(Icons.trip_origin, size: 16, color: Colors.green.shade200),
                  const SizedBox(width: 8),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          trans.get('start_location'),
                          style: GoogleFonts.manrope(fontSize: 9, color: Colors.white70),
                        ),
                        Text(
                          _formatStartLocation(currentTrip!),
                          style: GoogleFonts.manrope(fontSize: 11, fontWeight: FontWeight.w500, color: Colors.white),
                          maxLines: 2,
                          overflow: TextOverflow.ellipsis,
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ],
            const SizedBox(height: 6),
            Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Icon(Icons.my_location, size: 16, color: _currentLivePosition != null ? Colors.amber.shade200 : Colors.white54),
                const SizedBox(width: 8),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        trans.get('current_location_live'),
                        style: GoogleFonts.manrope(fontSize: 9, color: Colors.white70),
                      ),
                      Text(
                        _currentLivePosition != null
                            ? (_currentLocationName != null && _currentLocationName!.isNotEmpty
                                ? _currentLocationName!
                                : '${_currentLivePosition!.latitude.toStringAsFixed(5)}, ${_currentLivePosition!.longitude.toStringAsFixed(5)}')
                            : (trans.language == 'sw' ? 'Inapakua...' : 'Loading...'),
                        style: GoogleFonts.manrope(
                          fontSize: 11,
                          fontWeight: _currentLivePosition != null ? FontWeight.w600 : FontWeight.w500,
                          color: Colors.white,
                        ),
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                      ),
                    ],
                  ),
                ),
              ],
            ),
            if (progressValue != null && progressText.isNotEmpty) ...[
              const SizedBox(height: 10),
              ClipRRect(
                borderRadius: BorderRadius.circular(4),
                child: LinearProgressIndicator(
                  value: progressValue,
                  backgroundColor: Colors.white.withOpacity(0.2),
                  valueColor: const AlwaysStoppedAnimation<Color>(Colors.white),
                  minHeight: 5,
                ),
              ),
              const SizedBox(height: 4),
              Text(
                progressText,
                style: GoogleFonts.manrope(
                  fontSize: 9,
                  color: Colors.white70,
                ),
                textAlign: TextAlign.right,
              ),
            ],
            const SizedBox(height: 12),
            Row(
              children: [
                Expanded(
                  child: ElevatedButton(
                    onPressed: () => Navigator.push(
                      context,
                      MaterialPageRoute(
                        builder: (_) => TripDetailsScreen(trip: currentTrip),
                      ),
                    ).then((_) => _loadUser()),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: Colors.white,
                      foregroundColor: primary,
                      padding: const EdgeInsets.symmetric(vertical: 10),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(8),
                      ),
                    ),
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        const Icon(Icons.list_alt, size: 18),
                        const SizedBox(width: 6),
                        Text(
                          trans.get('view_trip_details'),
                          style: GoogleFonts.manrope(
                            fontWeight: FontWeight.w700,
                            fontSize: 11,
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: OutlinedButton(
                    onPressed: () => Navigator.push(
                      context,
                      MaterialPageRoute(
                        builder: (_) => ActiveTripMapScreen(trip: currentTrip),
                      ),
                    ),
                    style: OutlinedButton.styleFrom(
                      foregroundColor: Colors.white,
                      side: const BorderSide(color: Colors.white70),
                      padding: const EdgeInsets.symmetric(vertical: 10),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(8),
                      ),
                    ),
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        const Icon(Icons.map, size: 18),
                        const SizedBox(width: 6),
                        Text(
                          trans.get('view_on_map'),
                          style: GoogleFonts.manrope(
                            fontWeight: FontWeight.w700,
                            fontSize: 11,
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  bool _hasStartLocation(Map<String, dynamic>? trip) {
    if (trip == null) return false;
    final lat = trip['start_latitude'];
    final lng = trip['start_longitude'];
    return lat != null && lng != null && lat.toString().trim().isNotEmpty && lng.toString().trim().isNotEmpty;
  }

  String _formatStartLocation(Map<String, dynamic> trip) {
    final name = trip['start_location_name']?.toString()?.trim();
    if (name != null && name.isNotEmpty) return name;
    final tripId = trip['id']?.toString();
    if (tripId != null && tripId == _resolvedStartLocationTripId && _resolvedStartLocationName != null && _resolvedStartLocationName!.isNotEmpty) {
      return _resolvedStartLocationName!;
    }
    final lat = trip['start_latitude'];
    final lng = trip['start_longitude'];
    if (lat == null || lng == null) return '—';
    final la = num.tryParse(lat.toString());
    final lo = num.tryParse(lng.toString());
    if (la == null || lo == null) return '—';
    return '${la.toStringAsFixed(5)}, ${lo.toStringAsFixed(5)}';
  }

  Widget _financesGrid(AppTranslations trans) {
    return Row(
      children: [
        Expanded(
          child: _financeCard(
            trans,
            icon: Icons.account_balance_wallet_outlined,
            iconColor: Colors.green,
            title: trans.get('allowance'),
            subtitle: trans.get('eligible'),
            value: '\$500.00',
            footerLabel: trans.get('paid'),
            footerValue: '\$100',
          ),
        ),
        const SizedBox(width: 10),
        Expanded(
          child: _financeCard(
            trans,
            icon: Icons.payments_outlined,
            iconColor: Colors.blue,
            title: trans.get('cash_advance'),
            subtitle: trans.get('balance'),
            value: '\$50.00',
            footerLabel: trans.get('issued'),
            footerValue: '\$200',
          ),
        ),
      ],
    );
  }

  Widget _financeCard(
    AppTranslations trans, {
    required IconData icon,
    required Color iconColor,
    required String title,
    required String subtitle,
    required String value,
    required String footerLabel,
    required String footerValue,
  }) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: Colors.black12),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Icon(icon, color: iconColor, size: 18),
              Text(
                title,
                style: GoogleFonts.manrope(
                  fontSize: 9,
                  fontWeight: FontWeight.w700,
                  color: Colors.black38,
                ),
              ),
            ],
          ),
          const SizedBox(height: 8),
          Text(
            subtitle,
            style: GoogleFonts.manrope(
              fontSize: 11,
              color: Colors.black54,
            ),
          ),
          Text(
            value,
            style: GoogleFonts.manrope(
              fontSize: 15,
              fontWeight: FontWeight.w700,
              color: Colors.black87,
            ),
          ),
          const Divider(height: 16),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                footerLabel,
                style: GoogleFonts.manrope(
                  fontSize: 9,
                  color: Colors.black38,
                ),
              ),
              Text(
                footerValue,
                style: GoogleFonts.manrope(
                  fontSize: 9,
                  fontWeight: FontWeight.w700,
                  color: Colors.black54,
                ),
              ),
            ],
          ),
          const SizedBox(height: 10),
          SizedBox(
            width: double.infinity,
            child: TextButton(
              onPressed: () {},
              style: TextButton.styleFrom(
                backgroundColor: Colors.grey.shade200,
                padding: const EdgeInsets.symmetric(vertical: 8),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(8),
                ),
              ),
              child: Text(
                trans.get('request'),
                style: GoogleFonts.manrope(
                  fontSize: 11,
                  fontWeight: FontWeight.w700,
                  color: Colors.black87,
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _upcomingTripsCard(AppTranslations trans) {
    if (_loadingTrips) {
      return Container(
        padding: const EdgeInsets.all(32),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(10),
          border: Border.all(color: Colors.black12),
        ),
        child: const Center(
          child: CircularProgressIndicator(),
        ),
      );
    }

    if (_upcomingTrips.isEmpty) {
      return Container(
        padding: const EdgeInsets.all(24),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(10),
          border: Border.all(color: Colors.black12),
        ),
        child: Center(
          child: Column(
            children: [
              Icon(Icons.event_busy, size: 48, color: Colors.grey.shade400),
              const SizedBox(height: 8),
              Text(
                trans.language == 'sw' ? 'Hakuna safari zinazokuja' : 'No upcoming trips',
                style: GoogleFonts.manrope(
                  fontSize: 13,
                  fontWeight: FontWeight.w600,
                  color: Colors.black54,
                ),
              ),
            ],
          ),
        ),
      );
    }

    // Show only first 2 trips
    final displayTrips = _upcomingTrips.take(2).toList();

    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: Colors.black12),
      ),
      child: Column(
        children: [
          for (int i = 0; i < displayTrips.length; i++) ...[
            if (i > 0) const Divider(height: 1, color: Colors.black12),
            _upcomingTripItem(displayTrips[i]),
          ],
        ],
      ),
    );
  }

  Widget _upcomingTripItem(Map<String, dynamic> trip) {
    final origin = trip['origin_location']?.toString() ?? 'Unknown';
    final destination = trip['destination_location']?.toString() ?? 'Unknown';
    final startDateStr = trip['planned_start_date']?.toString();
    final trans = AppTranslations(Provider.of<LanguageProvider>(context, listen: false).currentLanguage);
    
    String displayDate = 'Not scheduled';
    String countdownText = '';
    Color countdownColor = Colors.black54;
    
    if (startDateStr != null && startDateStr.isNotEmpty) {
      try {
        final startDate = DateTime.parse(startDateStr);
        displayDate = _formatTripDate(startDate);
        
        // Calculate countdown
        final now = DateTime.now();
        if (startDate.isAfter(now)) {
          final diff = startDate.difference(now);
          
          if (diff.inDays >= 1) {
            final days = diff.inDays;
            final hours = diff.inHours % 24;
            countdownText = trans.language == 'sw'
                ? 'Siku $days, Masaa $hours zilizobaki'
                : '$days days, $hours hrs left';
            countdownColor = Colors.blue;
          } else if (diff.inHours >= 1) {
            final hours = diff.inHours;
            final minutes = diff.inMinutes % 60;
            countdownText = trans.language == 'sw'
                ? 'Masaa $hours, Dakika $minutes zilizobaki'
                : '$hours hrs, $minutes mins left';
            countdownColor = Colors.orange;
          } else if (diff.inMinutes > 0) {
            final minutes = diff.inMinutes;
            countdownText = trans.language == 'sw'
                ? 'Dakika $minutes zilizobaki'
                : '$minutes mins left';
            countdownColor = Colors.red;
          } else {
            countdownText = trans.language == 'sw' ? 'Inaanza sasa!' : 'Starting now!';
            countdownColor = Colors.red;
          }
        }
      } catch (e) {
        displayDate = startDateStr;
      }
    }

    return ListTile(
      contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
      leading: Container(
        width: 36,
        height: 36,
        decoration: BoxDecoration(
          color: primary.withOpacity(0.1),
          borderRadius: BorderRadius.circular(8),
        ),
        child: const Icon(Icons.local_shipping, size: 18, color: primary),
      ),
      title: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            displayDate,
            style: GoogleFonts.manrope(
              fontSize: 13,
              fontWeight: FontWeight.w700,
              color: Colors.black87,
            ),
          ),
          if (countdownText.isNotEmpty) ...[
            const SizedBox(height: 4),
            Row(
              children: [
                Icon(Icons.timer, size: 14, color: countdownColor),
                const SizedBox(width: 4),
                Text(
                  countdownText,
                  style: GoogleFonts.manrope(
                    fontSize: 11,
                    fontWeight: FontWeight.w600,
                    color: countdownColor,
                  ),
                ),
              ],
            ),
          ],
        ],
      ),
      subtitle: Padding(
        padding: const EdgeInsets.only(top: 4),
        child: Text(
          '$origin → $destination',
          style: GoogleFonts.manrope(
            fontSize: 11,
            color: Colors.black54,
          ),
          maxLines: 1,
          overflow: TextOverflow.ellipsis,
        ),
      ),
      trailing: const Icon(Icons.chevron_right, size: 20, color: Colors.black38),
      onTap: () => Navigator.push(
        context,
        MaterialPageRoute(
          builder: (_) => TripDetailsScreen(trip: trip),
        ),
      ).then((_) => _loadUpcomingTrips()),
    );
  }

  String _formatTripDate(DateTime date) {
    final now = DateTime.now();
    final difference = date.difference(now);

    if (difference.inDays == 0) {
      final hour = date.hour.toString().padLeft(2, '0');
      final minute = date.minute.toString().padLeft(2, '0');
      return 'Today, $hour:$minute';
    } else if (difference.inDays == 1) {
      final hour = date.hour.toString().padLeft(2, '0');
      final minute = date.minute.toString().padLeft(2, '0');
      return 'Tomorrow, $hour:$minute';
    } else {
      final months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
      final hour = date.hour.toString().padLeft(2, '0');
      final minute = date.minute.toString().padLeft(2, '0');
      return '${date.day} ${months[date.month - 1]}, $hour:$minute';
    }
  }

  Widget _pendingClaimsCard(AppTranslations trans) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: Colors.black12),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Row(
                children: [
                  const Icon(Icons.description_outlined, color: Colors.amber, size: 18),
                  const SizedBox(width: 6),
                  Text(
                    trans.get('pending_claims'),
                    style: GoogleFonts.manrope(
                      fontSize: 13,
                      fontWeight: FontWeight.w700,
                      color: Colors.black87,
                    ),
                  ),
                ],
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                decoration: BoxDecoration(
                  color: Colors.amber.withOpacity(0.2),
                  borderRadius: BorderRadius.circular(999),
                ),
                child: Text(
                  '2 ${trans.get('active')}',
                  style: GoogleFonts.manrope(
                    fontSize: 11,
                    fontWeight: FontWeight.w700,
                    color: Colors.amber.shade800,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 6),
          Text(
            'Fuel reimbursement and Toll fees under review.',
            style: GoogleFonts.manrope(
              fontSize: 11,
              color: Colors.black54,
            ),
          ),
        ],
      ),
    );
  }

  Widget _notificationsCard(AppTranslations trans) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: Colors.black12),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              const Icon(Icons.info_outline, color: primary, size: 18),
              const SizedBox(width: 6),
              Text(
                trans.get('notifications'),
                style: GoogleFonts.manrope(
                  fontSize: 13,
                  fontWeight: FontWeight.w700,
                  color: Colors.black87,
                ),
              ),
              const SizedBox(width: 6),
              Container(
                width: 6,
                height: 6,
                decoration: const BoxDecoration(
                  color: Colors.red,
                  shape: BoxShape.circle,
                ),
              ),
            ],
          ),
          const SizedBox(height: 6),
          Text(
            'New safety guidelines updated for DC B.',
            style: GoogleFonts.manrope(
              fontSize: 11,
              fontWeight: FontWeight.w500,
              color: Colors.black87,
            ),
          ),
          Text(
            '2 hours ago',
            style: GoogleFonts.manrope(
              fontSize: 9,
              color: Colors.black38,
            ),
          ),
        ],
      ),
    );
  }

  Widget _bottomNav(AppTranslations trans) {
    return Container(
      padding: EdgeInsets.only(
        left: 12,
        right: 12,
        top: 8,
        bottom: MediaQuery.of(context).padding.bottom + 8,
      ),
      decoration: BoxDecoration(
        color: Colors.white,
        border: Border(
          top: BorderSide(color: Colors.black12),
        ),
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceAround,
        children: [
          _navItem(0, Icons.home, trans.get('home')),
          _navItem(1, Icons.route, trans.get('trips')),
          _navItem(2, Icons.account_balance_wallet_outlined, trans.get('wallet')),
          _navItem(3, Icons.person_outline, trans.get('profile')),
        ],
      ),
    );
  }

  Widget _navItem(int index, IconData icon, String label) {
    final selected = _selectedIndex == index;
    return GestureDetector(
      onTap: () => setState(() => _selectedIndex = index),
      behavior: HitTestBehavior.opaque,
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(
            icon,
            size: 22,
            color: selected ? primary : Colors.black38,
          ),
          const SizedBox(height: 2),
          Text(
            label,
            style: GoogleFonts.manrope(
              fontSize: 9,
              fontWeight: selected ? FontWeight.w700 : FontWeight.w500,
              color: selected ? primary : Colors.black38,
            ),
          ),
        ],
      ),
    );
  }
}

// Dialog to show all upcoming trips
class _UpcomingTripsDialog extends StatelessWidget {
  final List<Map<String, dynamic>> trips;
  final VoidCallback onRefresh;

  const _UpcomingTripsDialog({
    required this.trips,
    required this.onRefresh,
  });

  static const Color primary = Color(0xFF135BEC);

  @override
  Widget build(BuildContext context) {
    final trans = AppTranslations(Provider.of<LanguageProvider>(context).currentLanguage);

    return Dialog(
      backgroundColor: const Color(0xFFF6F6F8),
      child: Container(
        constraints: const BoxConstraints(maxWidth: 500, maxHeight: 600),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            // Header
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: Colors.white,
                border: Border(bottom: BorderSide(color: Colors.grey.shade200)),
              ),
              child: Row(
                children: [
                  const Icon(Icons.notifications, color: primary, size: 24),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Text(
                      trans.get('upcoming_trips'),
                      style: GoogleFonts.manrope(
                        fontSize: 16,
                        fontWeight: FontWeight.w700,
                        color: Colors.black87,
                      ),
                    ),
                  ),
                  IconButton(
                    onPressed: () {
                      onRefresh();
                      Navigator.pop(context);
                    },
                    icon: const Icon(Icons.refresh, size: 20),
                    color: Colors.black54,
                  ),
                  IconButton(
                    onPressed: () => Navigator.pop(context),
                    icon: const Icon(Icons.close, size: 20),
                    color: Colors.black54,
                  ),
                ],
              ),
            ),
            // Content
            Expanded(
              child: trips.isEmpty
                  ? Center(
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(Icons.event_busy, size: 64, color: Colors.grey.shade400),
                          const SizedBox(height: 16),
                          Text(
                            trans.language == 'sw' ? 'Hakuna safari zinazokuja' : 'No upcoming trips',
                            style: GoogleFonts.manrope(
                              fontSize: 14,
                              fontWeight: FontWeight.w600,
                              color: Colors.black54,
                            ),
                          ),
                          const SizedBox(height: 8),
                          Text(
                            trans.language == 'sw' 
                                ? 'Utapokea arifa safari mpya zinapotolewa'
                                : 'You\'ll be notified when new trips are assigned',
                            style: GoogleFonts.manrope(
                              fontSize: 12,
                              color: Colors.black38,
                            ),
                            textAlign: TextAlign.center,
                          ),
                        ],
                      ),
                    )
                  : ListView.separated(
                      padding: const EdgeInsets.all(16),
                      itemCount: trips.length,
                      separatorBuilder: (_, __) => const SizedBox(height: 12),
                      itemBuilder: (context, index) {
                        final trip = trips[index];
                        return _TripCard(trip: trip);
                      },
                    ),
            ),
          ],
        ),
      ),
    );
  }
}

// Individual trip card in the dialog
class _TripCard extends StatelessWidget {
  final Map<String, dynamic> trip;

  const _TripCard({required this.trip});

  static const Color primary = Color(0xFF135BEC);

  @override
  Widget build(BuildContext context) {
    final tripNumber = trip['trip_number']?.toString() ?? 'N/A';
    final origin = trip['origin_location']?.toString() ?? 'Unknown';
    final destination = trip['destination_location']?.toString() ?? 'Unknown';
    final startDateStr = trip['planned_start_date']?.toString();
    final cargo = trip['cargo_description']?.toString();
    final status = trip['status']?.toString() ?? 'pending';

    String displayDate = 'Not scheduled';
    String timeUntil = '';
    
    if (startDateStr != null && startDateStr.isNotEmpty) {
      try {
        final startDate = DateTime.parse(startDateStr);
        displayDate = _formatFullDate(startDate);
        timeUntil = _getTimeUntil(startDate);
      } catch (e) {
        displayDate = startDateStr;
      }
    }

    return Material(
      color: Colors.white,
      borderRadius: BorderRadius.circular(12),
      child: InkWell(
        onTap: () {
          Navigator.pop(context);
          Navigator.push(
            context,
            MaterialPageRoute(
              builder: (_) => TripDetailsScreen(trip: trip),
            ),
          );
        },
        borderRadius: BorderRadius.circular(12),
        child: Container(
          padding: const EdgeInsets.all(14),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(12),
            border: Border.all(color: Colors.grey.shade200),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.all(8),
                        decoration: BoxDecoration(
                          color: primary.withOpacity(0.1),
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: const Icon(Icons.local_shipping, color: primary, size: 20),
                      ),
                      const SizedBox(width: 10),
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            'Trip #$tripNumber',
                            style: GoogleFonts.manrope(
                              fontSize: 13,
                              fontWeight: FontWeight.w700,
                              color: Colors.black87,
                            ),
                          ),
                          if (timeUntil.isNotEmpty)
                            Text(
                              timeUntil,
                              style: GoogleFonts.manrope(
                                fontSize: 10,
                                color: Colors.orange.shade700,
                                fontWeight: FontWeight.w600,
                              ),
                            ),
                        ],
                      ),
                    ],
                  ),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                    decoration: BoxDecoration(
                      color: _getStatusColor(status).withOpacity(0.1),
                      borderRadius: BorderRadius.circular(999),
                    ),
                    child: Text(
                      status.toUpperCase(),
                      style: GoogleFonts.manrope(
                        fontSize: 9,
                        fontWeight: FontWeight.w700,
                        color: _getStatusColor(status),
                      ),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 12),
              Row(
                children: [
                  const Icon(Icons.calendar_today, size: 14, color: Colors.black54),
                  const SizedBox(width: 6),
                  Text(
                    displayDate,
                    style: GoogleFonts.manrope(
                      fontSize: 11,
                      color: Colors.black54,
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 8),
              Row(
                children: [
                  Column(
                    children: [
                      const Icon(Icons.location_on, size: 14, color: primary),
                      Container(
                        margin: const EdgeInsets.symmetric(vertical: 2),
                        width: 2,
                        height: 16,
                        decoration: BoxDecoration(
                          color: Colors.grey.shade300,
                          borderRadius: BorderRadius.circular(1),
                        ),
                      ),
                      Icon(Icons.flag, size: 14, color: Colors.grey.shade400),
                    ],
                  ),
                  const SizedBox(width: 8),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          origin,
                          style: GoogleFonts.manrope(
                            fontSize: 11,
                            fontWeight: FontWeight.w600,
                            color: Colors.black87,
                          ),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                        const SizedBox(height: 16),
                        Text(
                          destination,
                          style: GoogleFonts.manrope(
                            fontSize: 11,
                            fontWeight: FontWeight.w600,
                            color: Colors.black87,
                          ),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                      ],
                    ),
                  ),
                ],
              ),
              if (cargo != null && cargo.isNotEmpty) ...[
                const SizedBox(height: 8),
                Row(
                  children: [
                    const Icon(Icons.inventory_2_outlined, size: 14, color: Colors.black54),
                    const SizedBox(width: 6),
                    Expanded(
                      child: Text(
                        cargo,
                        style: GoogleFonts.manrope(
                          fontSize: 11,
                          color: Colors.black54,
                        ),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                  ],
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }

  String _formatFullDate(DateTime date) {
    final months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    final days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    final hour = date.hour.toString().padLeft(2, '0');
    final minute = date.minute.toString().padLeft(2, '0');
    return '${days[date.weekday - 1]}, ${date.day} ${months[date.month - 1]} ${date.year} at $hour:$minute';
  }

  String _getTimeUntil(DateTime date) {
    final now = DateTime.now();
    final difference = date.difference(now);

    if (difference.isNegative) {
      return 'Overdue';
    } else if (difference.inMinutes < 60) {
      return 'Starts in ${difference.inMinutes}m';
    } else if (difference.inHours < 24) {
      return 'Starts in ${difference.inHours}h';
    } else if (difference.inDays == 1) {
      return 'Starts tomorrow';
    } else if (difference.inDays < 7) {
      return 'Starts in ${difference.inDays} days';
    } else {
      return 'Starts in ${(difference.inDays / 7).floor()} weeks';
    }
  }

  Color _getStatusColor(String status) {
    switch (status.toLowerCase()) {
      case 'pending':
        return Colors.orange;
      case 'scheduled':
        return Colors.blue;
      case 'in_progress':
        return Colors.green;
      case 'completed':
        return Colors.grey;
      case 'cancelled':
        return Colors.red;
      default:
        return Colors.grey;
    }
  }
}
