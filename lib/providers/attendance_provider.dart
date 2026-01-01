import 'package:flutter/material.dart';
import '../models/attendance_model.dart';
import '../services/attendance_service.dart';

class AttendanceProvider with ChangeNotifier {
  final AttendanceService _attendanceService = AttendanceService();

  List<Attendance> _attendances = [];
  Attendance? _todayAttendance;
  Map<String, dynamic>? _statistics;
  bool _isLoading = false;

  List<Attendance> get attendances => _attendances;
  Attendance? get todayAttendance => _todayAttendance;
  Map<String, dynamic>? get statistics => _statistics;
  bool get isLoading => _isLoading;

  /// ✅ Check-in dengan GPS
  Future<Map<String, dynamic>> checkIn({
    required String photoPath,
    int? scheduleId,
    double? latitude,   // ✅ GPS BARU
    double? longitude,  // ✅ GPS BARU
  }) async {
    debugPrint('🔵 CHECK-IN: Starting...');
    debugPrint('📸 Photo path: $photoPath');
    debugPrint('📅 Schedule ID: $scheduleId');
    debugPrint('📍 Latitude: $latitude');
    debugPrint('📍 Longitude: $longitude');

    final result = await _attendanceService.checkIn(
      photoPath: photoPath,
      scheduleId: scheduleId,
      latitude: latitude,   // ✅ KIRIM GPS
      longitude: longitude, // ✅ KIRIM GPS
    );

    debugPrint('✅ CHECK-IN Result: $result');

    // ✅ Reload today attendance after check-in
    if (result['success'] == true) {
      await getTodayAttendance();
    }

    return result;
  }

  /// ✅ Check-out dengan GPS
  Future<Map<String, dynamic>> checkOut({
    required String photoPath,
    double? latitude,   // ✅ GPS BARU
    double? longitude,  // ✅ GPS BARU
  }) async {
    debugPrint('🔵 CHECK-OUT: Starting...');
    debugPrint('📸 Photo path: $photoPath');
    debugPrint('📍 Latitude: $latitude');
    debugPrint('📍 Longitude: $longitude');

    final result = await _attendanceService.checkOut(
      photoPath: photoPath,
      latitude: latitude,   // ✅ KIRIM GPS
      longitude: longitude, // ✅ KIRIM GPS
    );

    debugPrint('✅ CHECK-OUT Result: $result');

    // ✅ Reload today attendance after check-out
    if (result['success'] == true) {
      await getTodayAttendance();
    }

    return result;
  }

  /// ✅ Get Today's Attendance
  Future<void> getTodayAttendance() async {
    try {
      debugPrint('📅 Loading today attendance...');
      _todayAttendance = await _attendanceService.getTodayAttendance();
      debugPrint('✅ Today attendance loaded: ${_todayAttendance?.id}');
      notifyListeners();
    } catch (e) {
      debugPrint('❌ Error loading today attendance: $e');
    }
  }

  /// ✅ Load my attendance
  Future<void> loadMyAttendance({String? startDate, String? endDate}) async {
    _isLoading = true;
    notifyListeners();

    try {
      debugPrint('📋 Loading my attendance...');
      debugPrint('📅 Date range: $startDate to $endDate');

      _attendances = await _attendanceService.getMyAttendance(
        startDate: startDate,
        endDate: endDate,
      );

      debugPrint('✅ Loaded ${_attendances.length} attendance records');
      for (var a in _attendances) {
        debugPrint('   - ${a.date}: ${a.status} (user_id: ${a.userId})');
      }
    } catch (e) {
      debugPrint('❌ Error loading attendance: $e');
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  /// ✅ Load Statistics
  Future<void> loadStatistics({String? startDate, String? endDate}) async {
    try {
      debugPrint('📊 Loading statistics...');
      _statistics = await _attendanceService.getStatistics(
        startDate: startDate,
        endDate: endDate,
      );
      debugPrint('✅ Statistics loaded: $_statistics');
      notifyListeners();
    } catch (e) {
      debugPrint('❌ Error loading statistics: $e');
    }
  }

  /// ✅ Get statistics (tanpa save ke state)
  Future<Map<String, dynamic>?> getStatistics({
    String? startDate,
    String? endDate,
  }) async {
    try {
      return await _attendanceService.getStatistics(
        startDate: startDate,
        endDate: endDate,
      );
    } catch (e) {
      debugPrint('❌ Error loading statistics: $e');
      return null;
    }
  }

  /// Clear data (untuk logout)
  void clear() {
    _attendances = [];
    _todayAttendance = null;
    _statistics = null;
    _isLoading = false;
    notifyListeners();
  }
}