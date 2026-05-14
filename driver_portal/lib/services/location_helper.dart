import 'package:geocoding/geocoding.dart';

/// Reverse geocode coordinates to a readable location name from maps.
/// Returns a short address (e.g. "Mikocheni B, Dar es Salaam, Tanzania").
/// Uses all available Placemark fields to build a name.
Future<String?> getLocationNameFromCoordinates(double latitude, double longitude) async {
  try {
    final placemarks = await placemarkFromCoordinates(latitude, longitude);
    if (placemarks.isEmpty) return null;
    final p = placemarks.first;
    final parts = <String>[];
    // Use name (e.g. building/place), thoroughfare (street), then locality/area
    if (p.name != null && p.name!.isNotEmpty && p.name != p.street) parts.add(p.name!);
    if (p.street != null && p.street!.isNotEmpty) parts.add(p.street!);
    if (p.subThoroughfare != null && p.subThoroughfare!.isNotEmpty) parts.add(p.subThoroughfare!);
    if (p.subLocality != null && p.subLocality!.isNotEmpty) parts.add(p.subLocality!);
    if (p.locality != null && p.locality!.isNotEmpty) parts.add(p.locality!);
    if (p.subAdministrativeArea != null && p.subAdministrativeArea!.isNotEmpty) parts.add(p.subAdministrativeArea!);
    if (p.administrativeArea != null && p.administrativeArea!.isNotEmpty) parts.add(p.administrativeArea!);
    if (p.country != null && p.country!.isNotEmpty) parts.add(p.country!);
    if (parts.isEmpty) return null;
    return parts.join(', ');
  } catch (e) {
    return null;
  }
}
