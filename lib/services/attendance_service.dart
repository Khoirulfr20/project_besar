// attendance_service.dart
import 'api_service.dart';
import '../models/attendance_model.dart';

class AttendanceService {
  final ApiService _api = ApiService();

  // Check-in
  Future<Map<String, dynamic>> checkIn({
    required String photoPath,
    required double confidence,
    int? scheduleId,
    String? location,
    String? deviceInfo,
  }) async {
    try {
      final response = await _api.uploadFile(
        '/attendances/check-in',
        filePath: photoPath,
        fieldName: 'photo',
        data: {
          'confidence': confidence,
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

  // Check-out
  Future<Map<String, dynamic>> checkOut({
    required String photoPath,
    required double confidence,
    String? location,
    String? deviceInfo,
  }) async {
    try {
      final response = await _api.uploadFile(
        '/attendances/check-out',
        filePath: photoPath,
        fieldName: 'photo',
        data: {
          'confidence': confidence,
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

  // Get my attendance history
  Future<List<Attendance>> getMyAttendance({
    String? startDate,
    String? endDate,
  }) async {
    try {
      final queryParams = <String, dynamic>{};
      if (startDate != null) queryParams['start_date'] = startDate;
      if (endDate != null) queryParams['end_date'] = endDate;

      final response = await _api.get(
        '/attendances/my-attendance',
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

  // Get today's attendance
  Future<Attendance?> getTodayAttendance() async {
    try {
      final response = await _api.get('/attendances/today');

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

  // Get all attendances (Admin/Pimpinan only)
  Future<List<Attendance>> getAttendances({
    int? userId,
    int? scheduleId,
    String? date,
    String? startDate,
    String? endDate,
    String? status,
    int page = 1,
  }) async {
    try {
      final queryParams = <String, dynamic>{
        'page': page,
        if (userId != null) 'user_id': userId,
        if (scheduleId != null) 'schedule_id': scheduleId,
        if (date != null) 'date': date,
        if (startDate != null) 'start_date': startDate,
        if (endDate != null) 'end_date': endDate,
        if (status != null) 'status': status,
      };

      final response = await _api.get(
        '/attendances',
        queryParameters: queryParams,
      );

      if (response.statusCode == 200 && response.data['success']) {
        final data = response.data['data']['data'] as List;
        return data.map((json) => Attendance.fromJson(json)).toList();
      }
      return [];
    } catch (e) {
      throw Exception(_api.handleError(e));
    }
  }

  // Update attendance status (Admin/Pimpinan only)
  Future<bool> updateStatus({
    required int attendanceId,
    required String status,
    String? notes,
  }) async {
    try {
      final response = await _api.put(
        '/attendances/$attendanceId/status',
        data: {'status': status, if (notes != null) 'notes': notes},
      );

      return response.statusCode == 200 && response.data['success'];
    } catch (e) {
      throw Exception(_api.handleError(e));
    }
  }

  // Approve attendance (Admin/Pimpinan only)
  Future<bool> approve({required int attendanceId, String? notes}) async {
    try {
      final response = await _api.post(
        '/attendances/$attendanceId/approve',
        data: {if (notes != null) 'notes': notes},
      );

      return response.statusCode == 200 && response.data['success'];
    } catch (e) {
      throw Exception(_api.handleError(e));
    }
  }

  // Get attendance statistics
  Future<Map<String, dynamic>?> getStatistics({
    int? userId,
    String? startDate,
    String? endDate,
  }) async {
    try {
      final queryParams = <String, dynamic>{};
      if (userId != null) queryParams['user_id'] = userId;
      if (startDate != null) queryParams['start_date'] = startDate;
      if (endDate != null) queryParams['end_date'] = endDate;

      final response = await _api.get(
        '/attendances/statistics',
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

  // Get attendance history/logs (Admin/Pimpinan only)
  Future<List<dynamic>> getHistory(int attendanceId) async {
    try {
      final response = await _api.get('/attendances/$attendanceId/history');

      if (response.statusCode == 200 && response.data['success']) {
        return response.data['data'] as List;
      }
      return [];
    } catch (e) {
      throw Exception(_api.handleError(e));
    }
  }
}
