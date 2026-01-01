import 'package:flutter/material.dart';
import 'package:geolocator/geolocator.dart';
import 'package:permission_handler/permission_handler.dart';

/// LocationService
/// 
/// Service untuk handle GPS location di Flutter
/// - Request permission
/// - Get current position
/// - Calculate distance
/// - Validate location
class LocationService {
  /// Singleton pattern
  static final LocationService _instance = LocationService._internal();
  factory LocationService() => _instance;
  LocationService._internal();

  /// Office location dari config (hardcoded, sesuai backend)
  /// Nanti bisa diambil dari API jika diperlukan
  static const double officeLat = -6.213877086806657;
  static const double officeLon = 106.60451640840081;
  static const double officeRadius = 200.0; // meter


  /// Check apakah location permission sudah granted
  Future<bool> hasLocationPermission() async {
    final status = await Permission.location.status;
    return status.isGranted;
  }

  /// Request location permission
  /// Return true jika granted, false jika denied
  Future<bool> requestLocationPermission() async {
    final status = await Permission.location.request();
    
    if (status.isGranted) {
      debugPrint('✅ Location permission granted');
      return true;
    } else if (status.isDenied) {
      debugPrint('❌ Location permission denied');
      return false;
    } else if (status.isPermanentlyDenied) {
      debugPrint('⚠️ Location permission permanently denied');
      // Bisa buka settings
      await openAppSettings();
      return false;
    }
    
    return false;
  }

  /// Check apakah location service (GPS) aktif
  Future<bool> isLocationServiceEnabled() async {
    return await Geolocator.isLocationServiceEnabled();
  }

  /// Get current position
  /// Throw exception jika gagal
  Future<Position> getCurrentPosition() async {
    debugPrint('📍 Getting current position...');

    // Check permission
    if (!await hasLocationPermission()) {
      debugPrint('⚠️ No location permission, requesting...');
      final granted = await requestLocationPermission();
      if (!granted) {
        throw Exception('Izin lokasi ditolak. Aktifkan izin lokasi di pengaturan.');
      }
    }

    // Check GPS service
    if (!await isLocationServiceEnabled()) {
      throw Exception('GPS tidak aktif. Aktifkan GPS di pengaturan.');
    }

    try {
      // Get position dengan accuracy tinggi
      final position = await Geolocator.getCurrentPosition(
        // ignore: deprecated_member_use
        desiredAccuracy: LocationAccuracy.high,
        // ignore: deprecated_member_use
        timeLimit: const Duration(seconds: 10),
      );

      debugPrint('✅ Position obtained:');
      debugPrint('   Latitude: ${position.latitude}');
      debugPrint('   Longitude: ${position.longitude}');
      debugPrint('   Accuracy: ${position.accuracy}m');

      return position;
    } catch (e) {
      debugPrint('❌ Error getting position: $e');
      throw Exception('Gagal mendapatkan lokasi: ${e.toString()}');
    }
  }

  /// Calculate distance between two coordinates (Haversine formula)
  /// Return distance in meters
  double calculateDistance(
    double lat1, 
    double lon1, 
    double lat2, 
    double lon2
  ) {
    return Geolocator.distanceBetween(lat1, lon1, lat2, lon2);
  }

  /// Validate apakah user berada dalam radius kantor
  /// Return Map dengan info lengkap
  Future<Map<String, dynamic>> validateLocation() async {
    try {
      // Get current position
      final position = await getCurrentPosition();

      // Calculate distance to office
      final distance = calculateDistance(
        position.latitude,
        position.longitude,
        officeLat,
        officeLon,
      );

      // Validate
      final isValid = distance <= officeRadius;

      final result = {
        'valid': isValid,
        'latitude': position.latitude,
        'longitude': position.longitude,
        'distance': distance.round(), // Dibulatkan ke integer
        'max_radius': officeRadius.round(),
        'accuracy': position.accuracy.round(),
        'office_location': {
          'latitude': officeLat,
          'longitude': officeLon,
        },
      };

      debugPrint('═══════════════════════════════════════');
      debugPrint('📍 LOCATION VALIDATION RESULT');
      debugPrint('═══════════════════════════════════════');
      debugPrint('Valid: ${result['valid']}');
      debugPrint('Distance: ${result['distance']}m');
      debugPrint('Max Radius: ${result['max_radius']}m');
      debugPrint('Accuracy: ${result['accuracy']}m');
      debugPrint('═══════════════════════════════════════');

      return result;
    } catch (e) {
      debugPrint('❌ Location validation error: $e');
      rethrow;
    }
  }

  /// Get location info untuk display (tanpa validasi)
  Future<Map<String, dynamic>> getLocationInfo() async {
    try {
      final position = await getCurrentPosition();

      return {
        'latitude': position.latitude,
        'longitude': position.longitude,
        'accuracy': position.accuracy.round(),
      };
    } catch (e) {
      debugPrint('❌ Error getting location info: $e');
      rethrow;
    }
  }

  /// Format distance untuk display
  String formatDistance(double meters) {
    if (meters >= 1000) {
      final km = meters / 1000;
      return '${km.toStringAsFixed(1)} km';
    }
    return '${meters.round()} m';
  }

  /// Format koordinat untuk display
  String formatCoordinate(double lat, double lon) {
    return '${lat.toStringAsFixed(6)}, ${lon.toStringAsFixed(6)}';
  }

  /// Open app settings (untuk enable permission)
  Future<void> openSettings() async {
    await openAppSettings();
  }

  /// Get last known position (faster, tapi mungkin outdated)
  Future<Position?> getLastKnownPosition() async {
    try {
      return await Geolocator.getLastKnownPosition();
    } catch (e) {
      debugPrint('⚠️ No last known position: $e');
      return null;
    }
  }

  /// Check location settings dan buka dialog jika perlu
  Future<bool> checkAndRequestLocationSettings(BuildContext context) async {
    // Check permission
    if (!await hasLocationPermission()) {
      final granted = await requestLocationPermission();
      if (!granted) {
        if (context.mounted) {
          _showPermissionDialog(context);
        }
        return false;
      }
    }

    // Check GPS service
    if (!await isLocationServiceEnabled()) {
      if (context.mounted) {
        _showGpsDialog(context);
      }
      return false;
    }

    return true;
  }

  /// Show permission denied dialog
  void _showPermissionDialog(BuildContext context) {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Row(
          children: [
            Icon(Icons.location_off, color: Colors.red),
            SizedBox(width: 12),
            Text('Izin Lokasi Diperlukan'),
          ],
        ),
        content: const Text(
          'Aplikasi memerlukan akses lokasi untuk memvalidasi absensi. '
          'Silakan aktifkan izin lokasi di pengaturan.',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Batal'),
          ),
          ElevatedButton(
            onPressed: () {
              Navigator.pop(context);
              openSettings();
            },
            child: const Text('Buka Pengaturan'),
          ),
        ],
      ),
    );
  }

  /// Show GPS disabled dialog
  void _showGpsDialog(BuildContext context) {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Row(
          children: [
            Icon(Icons.gps_off, color: Colors.orange),
            SizedBox(width: 12),
            Text('GPS Tidak Aktif'),
          ],
        ),
        content: const Text(
          'GPS tidak aktif. Silakan aktifkan GPS di pengaturan perangkat '
          'untuk melanjutkan absensi.',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Batal'),
          ),
          ElevatedButton(
            onPressed: () {
              Navigator.pop(context);
              Geolocator.openLocationSettings();
            },
            child: const Text('Buka Pengaturan'),
          ),
        ],
      ),
    );
  }
}