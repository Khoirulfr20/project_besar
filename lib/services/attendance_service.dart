import 'api_service.dart';
import '../models/attendance_model.dart';

class AttendanceService {
  final ApiService _api = ApiService();

  /// Check-in dengan schedule_id
  Future<Map<String, dynamic>> checkIn({
    required String photoPath,
    int? scheduleId,
    String? location,
    String? deviceInfo,
  }) async {
    try {
      final response = await _api.uploadFile(
        '/attendance/check-in',
        filePath: photoPath,
        fieldName: 'photo',
        data: {
          if (scheduleId != null) 'schedule_id': scheduleId,
          if (location != null) 'location': location,
          if (deviceInfo != null) 'device_info': deviceInfo,
        },
      );

      if (response.statusCode == 201 && response.data['success']) {
        return {
          'success': true,
          'attendance': Attendance.fromJson(response.data['data']),
          'message': response.data['message'],
        };
      }
      return {
        'success': false,
        'message': response.data['message'] ?? 'Check-in gagal',
      };
    } catch (e) {
      return {'success': false, 'message': _api.handleError(e)};
    }
  }

  /// Check-out
  Future<Map<String, dynamic>> checkOut({
    required String photoPath,
    String? location,
    String? deviceInfo,
  }) async {
    try {
      final response = await _api.uploadFile(
        '/attendance/check-out',
        filePath: photoPath,
        fieldName: 'photo',
        data: {
          if (location != null) 'location': location,
          if (deviceInfo != null) 'device_info': deviceInfo,
        },
      );

      if (response.statusCode == 200 && response.data['success']) {
        return {
          'success': true,
          'attendance': Attendance.fromJson(response.data['data']),
          'message': response.data['message'],
        };
      }
      return {
        'success': false,
        'message': response.data['message'] ?? 'Check-out gagal',
      };
    } catch (e) {
      return {'success': false, 'message': _api.handleError(e)};
    }
  }

  /// Get my attendance history
  Future<List<Attendance>> getMyAttendance({
    String? startDate,
    String? endDate,
  }) async {
    try {
      final queryParams = <String, dynamic>{};
      if (startDate != null) queryParams['start_date'] = startDate;
      if (endDate != null) queryParams['end_date'] = endDate;

      final response = await _api.get(
        '/attendance/my',
        queryParameters: queryParams,
      );

      if (response.statusCode == 200 && response.data['success']) {
        final data = response.data['data'] as List;
        return data.map((json) => Attendance.fromJson(json)).toList();
      }
      return [];
    } catch (e) {
      throw Exception(_api.handleError(e));
    }
  }

  /// Get today's attendance
  Future<Attendance?> getTodayAttendance() async {
    try {
      final response = await _api.get('/attendance/today');

      if (response.statusCode == 200 && response.data['success']) {
        final data = response.data['data'];
        if (data != null) {
          return Attendance.fromJson(data);
        }
      }
      return null;
    } catch (e) {
      return null;
    }
  }

  /// Get attendance statistics
  Future<Map<String, dynamic>?> getStatistics({
    String? startDate,
    String? endDate,
  }) async {
    try {
      final queryParams = <String, dynamic>{};
      if (startDate != null) queryParams['start_date'] = startDate;
      if (endDate != null) queryParams['end_date'] = endDate;

      final response = await _api.get(
        '/attendance/statistics',
        queryParameters: queryParams,
      );

      if (response.statusCode == 200 && response.data['success']) {
        return response.data['data'];
      }
      return null;
    } catch (e) {
      throw Exception(_api.handleError(e));
    }
  }
}