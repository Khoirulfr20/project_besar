import 'api_service.dart';
import '../models/schedule_model.dart';

class ScheduleService {
  final ApiService _api = ApiService();

  /// Get all schedules (tidak berubah)
  Future<List<Schedule>> getSchedules({
    String? startDate,
    String? endDate,
    String? status,
    String? type,
    bool? upcoming,
    bool? today,
    int page = 1,
  }) async {
    try {
      final queryParams = {
        'page': page,
        if (startDate != null) 'start_date': startDate,
        if (endDate != null) 'end_date': endDate,
        if (status != null) 'status': status,
        if (type != null) 'type': type,
        if (upcoming != null) 'upcoming': upcoming ? '1' : '0',
        if (today != null) 'today': today ? '1' : '0',
      };

      final response = await _api.get('/schedules', queryParameters: queryParams);

      if (response.statusCode == 200 && response.data['success'] == true) {
        final list = response.data['data']['data'] as List;
        return list.map((json) => Schedule.fromJson(json)).toList();
      }
      return [];
    } catch (_) {
      return [];
    }
  }

  /// Get my schedules (fixed path)
  Future<List<Schedule>> getMySchedules({bool? upcoming, bool? today}) async {
    try {
      final response = await _api.get(
        '/schedules/my',
        queryParameters: {
          if (upcoming != null) 'upcoming': upcoming ? '1' : '0',
          if (today != null) 'today': today ? '1' : '0',
        },
      );

      if (response.statusCode == 200 && response.data['success'] == true) {
        final list = response.data['data'] as List;
        return list.map((e) => Schedule.fromJson(e)).toList();
      }
      return [];
    } catch (_) {
      return [];
    }
  }

  /// Get schedule detail (tidak diubah)
  Future<Schedule?> getScheduleById(int id) async {
    try {
      final response = await _api.get('/schedules/$id');

      if (response.statusCode == 200 && response.data['success'] == true) {
        return Schedule.fromJson(response.data['data']);
      }
      return null;
    } catch (_) {
      return null;
    }
  }

  /// Get active schedules today (fixed path)
  Future<List<Schedule>> getTodayActiveSchedules() async {
    try {
      final response = await _api.get('/schedules/my/today-active');

      if (response.statusCode == 200 && response.data['success'] == true) {
        final list = response.data['data'] as List;
        return list.map((json) => Schedule.fromJson(json)).toList();
      }
      return [];
    } catch (_) {
      return [];
    }
  }
}
