import 'package:flutter/material.dart';
import '../models/schedule_model.dart';
import '../services/schedule_service.dart';

// ============================================
// Schedule Provider
// ============================================
class ScheduleProvider with ChangeNotifier {
  final ScheduleService _service = ScheduleService();

  List<Schedule> _schedules = []; // Semua jadwal user
  List<Schedule> _todayActiveSchedules = []; // Jadwal aktif hari ini

  bool _isLoading = false;

  List<Schedule> get schedules => _schedules;
  List<Schedule> get todayActiveSchedules => _todayActiveSchedules;
  bool get isLoading => _isLoading;

  // ===========================================================
  // GET USER SCHEDULES (Option: upcoming, today)
  // ===========================================================
  Future<void> getMySchedules({bool? upcoming, bool? today}) async {
    _isLoading = true;
    notifyListeners();

    try {
      _schedules = await _service.getMySchedules(
        upcoming: upcoming,
        today: today,
      );
    } catch (e) {
      _schedules = []; // <-- agar tidak throw error
    }

    _isLoading = false;
    notifyListeners();
  }

  // ===========================================================
  // GET SCHEDULE DETAIL
  // ===========================================================
  Future<Schedule?> getScheduleById(int id) async {
    return await _service.getScheduleById(id);
  }

  // ===========================================================
  // GET TODAY ACTIVE SCHEDULES (IMPORTANT FOR FACE ABSENCE)
  // ===========================================================
  Future<void> loadTodayActiveSchedules() async {
    _isLoading = true;
    notifyListeners();

    try {
      _todayActiveSchedules = await _service.getTodayActiveSchedules();
    } catch (e) {
      _todayActiveSchedules = [];
    }

    _isLoading = false;
    notifyListeners();
  }

  // ===========================================================
  // OPTIONAL: RETURN SELECTED SCHEDULE AUTOMATICALLY
  // ===========================================================
  /// - Jika hanya 1 jadwal aktif → langsung return
  /// - Jika >1 → user harus memilih
  Schedule? get autoSelectedSchedule {
    if (_todayActiveSchedules.length == 1) {
      return _todayActiveSchedules.first;
    }
    return null; // Harus pilih manual
  }
}
