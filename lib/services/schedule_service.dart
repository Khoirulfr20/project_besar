import 'api_service.dart';
import '../models/schedule_model.dart';

class ScheduleService {
  final ApiService _api = ApiService();

  // Get all schedules
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
      final queryParams = <String, dynamic>{
        'page': page,
        if (startDate != null) 'start_date': startDate,
        if (endDate != null) 'end_date': endDate,
        if (status != null) 'status': status,
        if (type != null) 'type': type,
        if (upcoming != null) 'upcoming': upcoming ? '1' : '0',
        if (today != null) 'today': today ? '1' : '0',
      };

      final response = await _api.get(
        '/schedules',
        queryParameters: queryParams,
      );

      if (response.statusCode == 200 && response.data['success']) {
        final data = response.data['data']['data'] as List;
        return data.map((json) => Schedule.fromJson(json)).toList();
      }
      return [];
    } catch (e) {
      throw Exception(_api.handleError(e));
    }
  }

  // Get my schedules
  Future<List<Schedule>> getMySchedules({bool? upcoming, bool? today}) async {
    try {
      final queryParams = <String, dynamic>{
        if (upcoming != null) 'upcoming': upcoming ? '1' : '0',
        if (today != null) 'today': today ? '1' : '0',
      };

      final response = await _api.get(
        '/schedules/my-schedules',
        queryParameters: queryParams,
      );

      if (response.statusCode == 200 && response.data['success']) {
        final data = response.data['data'] as List;
        return data.map((json) => Schedule.fromJson(json)).toList();
      }
      return [];
    } catch (e) {
      throw Exception(_api.handleError(e));
    }
  }

  // Get schedule detail
  Future<Schedule?> getScheduleById(int id) async {
    try {
      final response = await _api.get('/schedules/$id');

      if (response.statusCode == 200 && response.data['success']) {
        return Schedule.fromJson(response.data['data']);
      }
      return null;
    } catch (e) {
      throw Exception(_api.handleError(e));
    }
  }

  // Create schedule (Admin/Pimpinan only)
  Future<Schedule?> createSchedule({
    required String title,
    String? description,
    required String date,
    required String startTime,
    required String endTime,
    String? location,
    required String type,
    List<int>? participantIds,
  }) async {
    try {
      final response = await _api.post(
        '/schedules',
        data: {
          'title': title,
          'description': description,
          'date': date,
          'start_time': startTime,
          'end_time': endTime,
          'location': location,
          'type': type,
          'participant_ids': participantIds,
        },
      );

      if (response.statusCode == 201 && response.data['success']) {
        return Schedule.fromJson(response.data['data']);
      }
      return null;
    } catch (e) {
      throw Exception(_api.handleError(e));
    }
  }

  // Update schedule (Admin/Pimpinan only)
  Future<Schedule?> updateSchedule({
    required int id,
    String? title,
    String? description,
    String? date,
    String? startTime,
    String? endTime,
    String? location,
    String? type,
    String? status,
    List<int>? participantIds,
  }) async {
    try {
      final data = <String, dynamic>{};
      if (title != null) data['title'] = title;
      if (description != null) data['description'] = description;
      if (date != null) data['date'] = date;
      if (startTime != null) data['start_time'] = startTime;
      if (endTime != null) data['end_time'] = endTime;
      if (location != null) data['location'] = location;
      if (type != null) data['type'] = type;
      if (status != null) data['status'] = status;
      if (participantIds != null) data['participant_ids'] = participantIds;

      final response = await _api.put('/schedules/$id', data: data);

      if (response.statusCode == 200 && response.data['success']) {
        return Schedule.fromJson(response.data['data']);
      }
      return null;
    } catch (e) {
      throw Exception(_api.handleError(e));
    }
  }

  // Delete schedule (Admin/Pimpinan only)
  Future<bool> deleteSchedule(int id) async {
    try {
      final response = await _api.delete('/schedules/$id');
      return response.statusCode == 200 && response.data['success'];
    } catch (e) {
      throw Exception(_api.handleError(e));
    }
  }
}
