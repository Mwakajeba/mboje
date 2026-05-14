import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:geolocator/geolocator.dart';
import 'package:permission_handler/permission_handler.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:latlong2/latlong.dart';
import '../../providers/language_provider.dart';
import '../../services/trips_service.dart';
import '../../services/location_helper.dart';

class UpdateLocationScreen extends StatefulWidget {
  const UpdateLocationScreen({super.key, this.trip});

  /// Current trip to update location for (from trip details). If null, submit is disabled.
  final Map<String, dynamic>? trip;

  @override
  State<UpdateLocationScreen> createState() => _UpdateLocationScreenState();
}

class _UpdateLocationScreenState extends State<UpdateLocationScreen> {
  static const Color primary = Color(0xFF135BEC);
  static const Color textPrimary = Color(0xFF111318);
  static const Color textSecondary = Color(0xFF616F89);
  static const int _commentMaxLength = 250;

  final TextEditingController _commentController = TextEditingController();
  final TripsService _tripsService = TripsService();
  final MapController _mapController = MapController();

  Position? _position;
  String? _locationName;
  bool _loadingLocation = true;
  bool _submitting = false;
  static const LatLng _defaultCenter = LatLng(-6.7924, 39.2083);

  @override
  void initState() {
    super.initState();
    _requestLocationAndFetch();
  }

  @override
  void dispose() {
    _commentController.dispose();
    super.dispose();
  }

  Future<void> _requestLocationAndFetch() async {
    setState(() => _loadingLocation = true);
    try {
      final status = await Permission.location.request();
      if (status.isGranted) {
        await _fetchLocation();
      } else {
        if (mounted) setState(() => _loadingLocation = false);
      }
    } catch (e) {
      if (mounted) setState(() => _loadingLocation = false);
    }
  }

  Future<void> _fetchLocation() async {
    try {
      final pos = await Geolocator.getCurrentPosition(
        desiredAccuracy: LocationAccuracy.high,
      );
      final name = await getLocationNameFromCoordinates(pos.latitude, pos.longitude);
      if (mounted) {
        setState(() {
          _position = pos;
          _locationName = name;
          _loadingLocation = false;
        });
        _mapController.move(LatLng(pos.latitude, pos.longitude), 15);
      }
    } catch (e) {
      if (mounted) setState(() => _loadingLocation = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final trans = AppTranslations(Provider.of<LanguageProvider>(context).currentLanguage);
    final trip = widget.trip;
    final tripId = trip?['id']?.toString();
    final hasTrip = tripId != null && tripId.isNotEmpty;
    final center = _position != null
        ? LatLng(_position!.latitude, _position!.longitude)
        : _defaultCenter;

    return Scaffold(
      backgroundColor: const Color(0xFFF6F6F8),
      body: SafeArea(
        child: Column(
          children: [
            _buildAppBar(context, trans),
            Expanded(
              child: SingleChildScrollView(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    _mapSection(trans, center),
                    if (!hasTrip)
                      Padding(
                        padding: const EdgeInsets.all(16),
                        child: Container(
                          padding: const EdgeInsets.all(12),
                          decoration: BoxDecoration(
                            color: Colors.orange.shade50,
                            borderRadius: BorderRadius.circular(12),
                            border: Border.all(color: Colors.orange.shade200),
                          ),
                          child: Row(
                            children: [
                              Icon(Icons.info_outline, color: Colors.orange.shade700, size: 24),
                              const SizedBox(width: 12),
                              Expanded(
                                child: Text(
                                  trans.language == 'sw'
                                      ? 'Hakuna safari iliyochaguliwa. Fungua safari inayoendelea kisha sasisha mahali.'
                                      : 'No trip selected. Open an active trip then update location.',
                                  style: GoogleFonts.manrope(fontSize: 12, color: textPrimary),
                                ),
                              ),
                            ],
                          ),
                        ),
                      )
                    else
                      _gpsStatus(trans),
                    _commentsSection(trans),
                    _quickUpdates(trans),
                    const SizedBox(height: 24),
                  ],
                ),
              ),
            ),
            _buildBottomCta(context, trans, hasTrip, tripId),
          ],
        ),
      ),
    );
  }

  Widget _buildAppBar(BuildContext context, AppTranslations trans) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 8),
      decoration: BoxDecoration(
        color: Colors.white,
        border: Border(bottom: BorderSide(color: Colors.grey.shade200)),
      ),
      child: Row(
        children: [
          IconButton(
            onPressed: () => Navigator.pop(context),
            icon: const Icon(Icons.arrow_back_ios, size: 20),
            color: textPrimary,
          ),
          Expanded(
            child: Text(
              trans.get('update_location_title'),
              style: GoogleFonts.manrope(
                fontSize: 16,
                fontWeight: FontWeight.w700,
                color: textPrimary,
              ),
              textAlign: TextAlign.center,
            ),
          ),
          const SizedBox(width: 48),
        ],
      ),
    );
  }

  Widget _mapSection(AppTranslations trans, LatLng center) {
    return Padding(
      padding: const EdgeInsets.all(16),
      child: ClipRRect(
        borderRadius: BorderRadius.circular(12),
        child: Stack(
          children: [
            AspectRatio(
              aspectRatio: 4 / 3,
              child: _loadingLocation
                  ? Container(
                      color: Colors.grey.shade200,
                      child: Center(
                        child: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            const CircularProgressIndicator(),
                            const SizedBox(height: 12),
                            Text(
                              trans.language == 'sw' ? 'Inapakua GPS...' : 'Loading GPS...',
                              style: GoogleFonts.manrope(fontSize: 12, color: textSecondary),
                            ),
                          ],
                        ),
                      ),
                    )
                  : FlutterMap(
                      mapController: _mapController,
                      options: MapOptions(
                        initialCenter: center,
                        initialZoom: 15,
                        onMapReady: () {
                          if (_position != null) {
                            _mapController.move(
                              LatLng(_position!.latitude, _position!.longitude),
                              15,
                            );
                          }
                        },
                      ),
                      children: [
                        TileLayer(
                          urlTemplate: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
                          userAgentPackageName: 'com.example.driver_portal',
                        ),
                        if (_position != null)
                          MarkerLayer(
                            markers: [
                              Marker(
                                point: LatLng(_position!.latitude, _position!.longitude),
                                width: 44,
                                height: 44,
                                child: Icon(Icons.my_location, color: primary, size: 36),
                              ),
                            ],
                          ),
                      ],
                    ),
            ),
            Positioned(
              top: 12,
              right: 12,
              child: Container(
                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                decoration: BoxDecoration(
                  color: Colors.white.withOpacity(0.95),
                  borderRadius: BorderRadius.circular(999),
                  boxShadow: [
                    BoxShadow(
                      color: Colors.black.withOpacity(0.1),
                      blurRadius: 6,
                    ),
                  ],
                ),
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Container(
                      width: 8,
                      height: 8,
                      decoration: BoxDecoration(
                        color: _position != null ? Colors.green : Colors.grey,
                        shape: BoxShape.circle,
                      ),
                    ),
                    const SizedBox(width: 6),
                    Text(
                      trans.get('current_position').toUpperCase(),
                      style: GoogleFonts.manrope(
                        fontSize: 9,
                        fontWeight: FontWeight.w700,
                        color: primary,
                        letterSpacing: 0.5,
                      ),
                    ),
                  ],
                ),
              ),
            ),
            if (!_loadingLocation && _position != null)
              Positioned(
                bottom: 12,
                right: 12,
                child: Material(
                  elevation: 2,
                  borderRadius: BorderRadius.circular(999),
                  child: InkWell(
                    onTap: _fetchLocation,
                    borderRadius: BorderRadius.circular(999),
                    child: Container(
                      width: 40,
                      height: 40,
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(999),
                      ),
                      child: const Icon(Icons.my_location, color: primary, size: 22),
                    ),
                  ),
                ),
              ),
          ],
        ),
      ),
    );
  }

  Widget _gpsStatus(AppTranslations trans) {
    final updatedAt = _position != null
        ? (trans.language == 'sw' ? 'Sasa' : 'Just now')
        : (trans.language == 'sw' ? 'Inapakua...' : 'Loading...');
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16),
      child: Container(
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(
          color: Colors.grey.shade50,
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: Colors.grey.shade200),
        ),
        child: Row(
          children: [
            Container(
              width: 40,
              height: 40,
              decoration: BoxDecoration(
                color: _position != null ? Colors.green.shade50 : Colors.grey.shade200,
                borderRadius: BorderRadius.circular(8),
              ),
              child: Icon(
                _position != null ? Icons.check_circle : Icons.gps_not_fixed,
                color: _position != null ? Colors.green.shade700 : Colors.grey,
                size: 24,
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    trans.get('gps_status_captured'),
                    style: GoogleFonts.manrope(
                      fontSize: 13,
                      fontWeight: FontWeight.w600,
                      color: textPrimary,
                    ),
                  ),
                  const SizedBox(height: 2),
                  Text(
                    _locationName ?? (_position != null
                        ? '${_position!.latitude.toStringAsFixed(5)}, ${_position!.longitude.toStringAsFixed(5)}'
                        : trans.get('gps_validated')),
                    style: GoogleFonts.manrope(fontSize: 11, color: textSecondary),
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                  ),
                ],
              ),
            ),
            Text(
              updatedAt,
              style: GoogleFonts.manrope(fontSize: 11, color: textSecondary),
            ),
          ],
        ),
      ),
    );
  }

  Widget _commentsSection(AppTranslations trans) {
    return Padding(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                trans.get('optional_comments'),
                style: GoogleFonts.manrope(
                  fontSize: 14,
                  fontWeight: FontWeight.w600,
                  color: textPrimary,
                ),
              ),
              Text(
                trans.get('character_limit'),
                style: GoogleFonts.manrope(
                  fontSize: 11,
                  fontWeight: FontWeight.w500,
                  color: textSecondary,
                ),
              ),
            ],
          ),
          const SizedBox(height: 8),
          TextField(
            controller: _commentController,
            maxLength: _commentMaxLength,
            maxLines: 5,
            decoration: InputDecoration(
              hintText: trans.language == 'sw'
                  ? 'mfano, Mgongo mkali karibu na daraja...'
                  : 'e.g., Heavy traffic near bridge, stopped for fuel...',
              hintStyle: GoogleFonts.manrope(fontSize: 13, color: textSecondary),
              filled: true,
              fillColor: Colors.white,
              border: OutlineInputBorder(
                borderRadius: BorderRadius.circular(12),
                borderSide: BorderSide(color: Colors.grey.shade300),
              ),
              enabledBorder: OutlineInputBorder(
                borderRadius: BorderRadius.circular(12),
                borderSide: BorderSide(color: Colors.grey.shade300),
              ),
              focusedBorder: OutlineInputBorder(
                borderRadius: BorderRadius.circular(12),
                borderSide: const BorderSide(color: primary, width: 2),
              ),
              contentPadding: const EdgeInsets.all(14),
            ),
            style: GoogleFonts.manrope(fontSize: 13, color: textPrimary),
            onChanged: (_) => setState(() {}),
          ),
        ],
      ),
    );
  }

  Widget _quickUpdates(AppTranslations trans) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            trans.get('quick_updates').toUpperCase(),
            style: GoogleFonts.manrope(
              fontSize: 10,
              fontWeight: FontWeight.w700,
              color: textSecondary,
              letterSpacing: 0.5,
            ),
          ),
          const SizedBox(height: 10),
          Wrap(
            spacing: 8,
            runSpacing: 8,
            children: [
              _quickTag(trans.get('construction')),
              _quickTag(trans.get('heavy_traffic')),
              _quickTag(trans.get('rest_stop')),
            ],
          ),
        ],
      ),
    );
  }

  Widget _quickTag(String label) {
    return Material(
      color: Colors.white,
      borderRadius: BorderRadius.circular(999),
      child: InkWell(
        onTap: () {
          final current = _commentController.text;
          final add = '$label; ';
          if (current.length + add.length <= _commentMaxLength) {
            _commentController.text = current + add;
            setState(() {});
          }
        },
        borderRadius: BorderRadius.circular(999),
        child: Container(
          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(999),
            border: Border.all(color: Colors.grey.shade300),
          ),
          child: Text(
            label,
            style: GoogleFonts.manrope(
              fontSize: 12,
              fontWeight: FontWeight.w500,
              color: textSecondary,
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildBottomCta(BuildContext context, AppTranslations trans, bool hasTrip, String? tripId) {
    final canSubmit = hasTrip && _position != null && !_submitting;

    return Container(
      padding: EdgeInsets.only(
        left: 16,
        right: 16,
        top: 16,
        bottom: MediaQuery.of(context).padding.bottom + 24,
      ),
      decoration: BoxDecoration(
        color: Colors.white,
        border: Border(top: BorderSide(color: Colors.grey.shade200)),
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          SizedBox(
            width: double.infinity,
            height: 52,
            child: FilledButton.icon(
              onPressed: canSubmit
                  ? () async {
                      if (tripId == null || _position == null) return;
                      setState(() => _submitting = true);
                      final result = await _tripsService.updateTripLocation(
                        tripId,
                        _position!.latitude,
                        _position!.longitude,
                        locationName: _locationName,
                        notes: _commentController.text.trim().isEmpty
                            ? null
                            : _commentController.text.trim(),
                      );
                      if (!mounted) return;
                      setState(() => _submitting = false);
                      ScaffoldMessenger.of(context).showSnackBar(
                        SnackBar(
                          content: Text(
                            result['success'] == true
                                ? (trans.language == 'sw' ? 'Mahali pamewasilishwa.' : 'Location submitted.')
                                : result['message']?.toString() ?? 'Error',
                          ),
                          backgroundColor: result['success'] == true ? Colors.green : Colors.red,
                        ),
                      );
                      if (result['success'] == true) Navigator.pop(context);
                    }
                  : null,
              icon: _submitting
                  ? const SizedBox(
                      width: 22,
                      height: 22,
                      child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                    )
                  : const Icon(Icons.send, size: 20),
              label: Text(
                trans.get('submit_update'),
                style: GoogleFonts.manrope(
                  fontSize: 15,
                  fontWeight: FontWeight.w700,
                ),
              ),
              style: FilledButton.styleFrom(
                backgroundColor: primary,
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(12),
                ),
              ),
            ),
          ),
          const SizedBox(height: 10),
          Text(
            trans.get('current_accuracy'),
            style: GoogleFonts.manrope(
              fontSize: 11,
              fontWeight: FontWeight.w500,
              color: textSecondary,
            ),
          ),
        ],
      ),
    );
  }
}
