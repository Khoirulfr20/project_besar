import 'package:permission_handler/permission_handler.dart';

class PermissionsHelper {
  /// Request camera permission
  static Future<bool> requestCameraPermission() async {
    final status = await Permission.camera.request();
    return status.isGranted;
  }

  /// Request storage permission
  static Future<bool> requestStoragePermission() async {
    final status = await Permission.storage.request();
    return status.isGranted;
  }

  /// Request location permission
  static Future<bool> requestLocationPermission() async {
    final status = await Permission.location.request();
    return status.isGranted;
  }

  /// Check if camera permission is granted
  static Future<bool> checkCameraPermission() async {
    return await Permission.camera.isGranted;
  }

  /// Check if storage permission is granted
  static Future<bool> checkStoragePermission() async {
    return await Permission.storage.isGranted;
  }

  /// Check if location permission is granted
  static Future<bool> checkLocationPermission() async {
    return await Permission.location.isGranted;
  }

  /// Open app settings (FIXED!)
  static Future<void> openAppSettings() async {
    await openAppSettings(); // ✅ DIPERBAIKI: gunakan dari package
  }

  /// Request all permissions at once
  static Future<Map<Permission, PermissionStatus>>
      requestAllPermissions() async {
    return await [
      Permission.camera,
      Permission.location,
      Permission.storage,
    ].request();
  }

  /// Check if camera permission is permanently denied
  static Future<bool> isCameraPermissionPermanentlyDenied() async {
    final status = await Permission.camera.status;
    return status.isPermanentlyDenied;
  }
}
