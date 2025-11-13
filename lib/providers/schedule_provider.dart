import 'package:flutter/material.dart';
import '../models/schedule_model.dart';
import '../services/schedule_service.dart';

// ============================================
// Schedule Provider
// ============================================
class ScheduleProvider with ChangeNotifier {
  final ScheduleService _service = ScheduleService();

  List<Schedule> _schedules = [];
  bool _isLoading = false;

  List<Schedule> get schedules => _schedules;
  bool get isLoading => _isLoading;

  Future<void> getMySchedules({bool? upcoming, bool? today}) async {
    _isLoading = true;
    notifyListeners();

    _schedules = await _service.getMySchedules(
      upcoming: upcoming,
      today: today,
    );

    _isLoading = false;
    notifyListeners();
  }

  Future<Schedule?> getScheduleById(int id) async {
    return await _service.getScheduleById(id);
  }
}
