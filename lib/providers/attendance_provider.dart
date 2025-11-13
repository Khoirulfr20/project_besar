import 'package:flutter/material.dart';
import 'package:smart_attendance/models/notification_model.dart';
import '../models/attendance_model.dart';
import '../services/attendance_service.dart';

// ============================================
// Attendance Provider
// ============================================
class AttendanceProvider with ChangeNotifier {
  final AttendanceService _service = AttendanceService();

  List<Attendance> _attendances = [];
  Attendance? _todayAttendance;
  AttendanceStatistics? _statistics;
  bool _isLoading = false;

  List<Attendance> get attendances => _attendances;
  Attendance? get todayAttendance => _todayAttendance;
  AttendanceStatistics? get statistics => _statistics;
  bool get isLoading => _isLoading;

  Future<void> getTodayAttendance() async {
    _todayAttendance = await _service.getTodayAttendance();
    notifyListeners();
  }

  Future<Map<String, dynamic>> checkIn({
    required String photoPath,
    required double confidence,
    int? scheduleId,
    String? location,
  }) async {
    final result = await _service.checkIn(
      photoPath: photoPath,
      confidence: confidence,
      scheduleId: scheduleId,
      location: location,
    );

    if (result['success']) {
      await getTodayAttendance();
    }

    return result;
  }

  Future<Map<String, dynamic>> checkOut({
    required String photoPath,
    required double confidence,
    String? location,
  }) async {
    final result = await _service.checkOut(
      photoPath: photoPath,
      confidence: confidence,
      location: location,
    );

    if (result['success']) {
      await getTodayAttendance();
    }

    return result;
  }

  Future<void> loadMyAttendance({String? startDate, String? endDate}) async {
    _isLoading = true;
    notifyListeners();

    _attendances = await _service.getMyAttendance(
      startDate: startDate,
      endDate: endDate,
    );

    _isLoading = false;
    notifyListeners();
  }

  Future<void> loadStatistics() async {
    final data = await _service.getStatistics();
    if (data != null) {
      _statistics = AttendanceStatistics.fromJson(data);
      notifyListeners();
    }
  }
}
