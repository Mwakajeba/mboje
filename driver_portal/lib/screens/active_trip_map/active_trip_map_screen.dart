import 'dart:async';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:geolocator/geolocator.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:latlong2/latlong.dart';
import '../../providers/language_provider.dart';
import '../../services/location_helper.dart';

/// Shows start location (where driver started the trip) and live current GPS on an OSM map.
class ActiveTripMapScreen extends StatefulWidget {
  const ActiveTripMapScreen({
    super.key,
    required this.trip,
  });

  final Map<String, dynamic> trip;

  @override
  State<ActiveTripMapScreen> createState() => _ActiveTripMapScreenState();
}

class _ActiveTripMapScreenState extends State<ActiveTripMapScreen> {
  static const Color primary = Color(0xFF135BEC);
  final MapController _mapController = MapController();
  Position? _currentPosition;
  String? _currentLocationName;
  Timer? _locationTimer;
  bool _loading = true;
  static const LatLng _defaultCenter = LatLng(-6.7924, 39.2083);

  @override
  void initState() {
    super.initState();
    _fetchLocation();
    _locationTimer = Timer.periodic(const Duration(seconds: 15), (_) => _fetchLocation());
  }

  @override
  void dispose() {
    _locationTimer?.cancel();
    super.dispose();
  }

  Future<void> _fetchLocation() async {
    try {
      final pos = await Geolocator.getCurrentPosition(
        desiredAccuracy: LocationAccuracy.high,
      );
      final name = await getLocationNameFromCoordinates(pos.latitude, pos.longitude);
      if (mounted) {
        setState(() {
          _currentPosition = pos;
          _currentLocationName = name;
          _loading = false;
        });
        _mapController.move(LatLng(pos.latitude, pos.longitude), 14);
      }
    } catch (e) {
      if (mounted) setState(() => _loading = false);
    }
  }

  LatLng? get _startLatLng {
    final lat = widget.trip['start_latitude'];
    final lng = widget.trip['start_longitude'];
    if (lat != null && lng != null) {
      final la = num.tryParse(lat.toString());
      final lo = num.tryParse(lng.toString());
      if (la != null && lo != null) return LatLng(la.toDouble(), lo.toDouble());
    }
    return null;
  }

  String? get _startLocationName {
    final name = widget.trip['start_location_name']?.toString()?.trim();
    return (name != null && name.isNotEmpty) ? name : null;
  }

  LatLng get _mapCenter {
    final start = _startLatLng;
    if (_currentPosition != null && start != null) {
      return LatLng(
        (start.latitude + _currentPosition!.latitude) / 2,
        (start.longitude + _currentPosition!.longitude) / 2,
      );
    }
    if (_currentPosition != null) {
      return LatLng(_currentPosition!.latitude, _currentPosition!.longitude);
    }
    if (start != null) return start;
    return _defaultCenter;
  }

  List<Marker> _buildMarkers(BuildContext context) {
    final List<Marker> m = [];
    final start = _startLatLng;
    final isSw = Provider.of<LanguageProvider>(context, listen: false).currentLanguage == 'sw';
    if (start != null) {
      m.add(
        Marker(
          point: start,
          width: 44,
          height: 44,
          child: Icon(Icons.trip_origin, color: Colors.green.shade700, size: 36),
        ),
      );
    }
    if (_currentPosition != null) {
      m.add(
        Marker(
          point: LatLng(_currentPosition!.latitude, _currentPosition!.longitude),
          width: 44,
          height: 44,
          child: Icon(Icons.my_location, color: primary, size: 36),
        ),
      );
    }
    return m;
  }

  @override
  Widget build(BuildContext context) {
    final trans = AppTranslations(Provider.of<LanguageProvider>(context).currentLanguage);
    final start = _startLatLng;
    final hasStart = start != null;

    return Scaffold(
      backgroundColor: const Color(0xFFF6F6F8),
      appBar: AppBar(
        title: Text(
          trans.get('view_on_map'),
          style: GoogleFonts.manrope(fontWeight: FontWeight.w700),
        ),
        backgroundColor: Colors.white,
        foregroundColor: primary,
        elevation: 0,
      ),
      body: Column(
        children: [
          if (hasStart || _currentPosition != null)
            Container(
              width: double.infinity,
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
              color: Colors.white,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  if (hasStart) ...[
                    Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Icon(Icons.trip_origin, size: 18, color: Colors.green.shade700),
                        const SizedBox(width: 8),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                trans.get('start_location'),
                                style: GoogleFonts.manrope(fontSize: 10, color: Colors.black54),
                              ),
                              const SizedBox(height: 2),
                              Text(
                                _startLocationName ?? '${start.latitude.toStringAsFixed(5)}, ${start.longitude.toStringAsFixed(5)}',
                                style: GoogleFonts.manrope(fontSize: 12, color: Colors.black87),
                                maxLines: 2,
                                overflow: TextOverflow.ellipsis,
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                    if (_currentPosition != null) const SizedBox(height: 8),
                  ],
                  if (_currentPosition != null)
                    Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Icon(Icons.my_location, size: 18, color: primary),
                        const SizedBox(width: 8),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                trans.get('current_location_live'),
                                style: GoogleFonts.manrope(fontSize: 10, color: Colors.black54),
                              ),
                              const SizedBox(height: 2),
                              Text(
                                _currentLocationName ?? '${_currentPosition!.latitude.toStringAsFixed(5)}, ${_currentPosition!.longitude.toStringAsFixed(5)}',
                                style: GoogleFonts.manrope(fontSize: 12, fontWeight: FontWeight.w600, color: primary),
                                maxLines: 2,
                                overflow: TextOverflow.ellipsis,
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                ],
              ),
            ),
          Expanded(
            child: _loading && _currentPosition == null && _startLatLng == null
                ? const Center(child: CircularProgressIndicator())
                : FlutterMap(
                    mapController: _mapController,
                    options: MapOptions(
                      initialCenter: _mapCenter,
                      initialZoom: 14,
                      onMapReady: () {
                        if (_startLatLng != null || _currentPosition != null) {
                          _mapController.move(_mapCenter, 14);
                        }
                      },
                    ),
                    children: [
                      TileLayer(
                        urlTemplate: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
                        userAgentPackageName: 'com.example.driver_portal',
                      ),
                      MarkerLayer(markers: _buildMarkers(context)),
                    ],
                  ),
          ),
          SafeArea(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: SizedBox(
                width: double.infinity,
                child: OutlinedButton.icon(
                  onPressed: _fetchLocation,
                  icon: const Icon(Icons.refresh, size: 20),
                  label: Text(trans.get('current_position'), style: GoogleFonts.manrope(fontWeight: FontWeight.w600)),
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}
