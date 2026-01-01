import 'package:dio/dio.dart';
import '../models/attendance_model.dart';
import 'api_service.dart';
import '../utils/app_logger.dart';

class AttendanceService {
  final ApiService _api = ApiService();

  /// ✅ PENTING: Header untuk identify request dari mobile app
  Map<String, String> get _mobileHeaders => {
        'X-Source': 'mobile-app', // ✅ Backend akan cek header ini
      };

  /// ✅ CHECK-IN dengan GPS
  Future<Map<String, dynamic>> checkIn({
    required String photoPath,
    int? scheduleId,
    double? latitude, // ✅ GPS BARU
    double? longitude, // ✅ GPS BARU
  }) async {
    try {
      AppLogger.debug('═══════════════════════════════════════');
      AppLogger.debug('📸 CHECK-IN SERVICE START');
      AppLogger.debug('═══════════════════════════════════════');
      AppLogger.debug('Photo Path: $photoPath');
      AppLogger.debug('Schedule ID: $scheduleId');
      AppLogger.debug('Latitude: $latitude');
      AppLogger.debug('Longitude: $longitude');
      AppLogger.debug('═══════════════════════════════════════');

      // ✅ Prepare FormData
      final formData = FormData.fromMap({
        'photo': await MultipartFile.fromFile(
          photoPath,
          filename: photoPath.split('/').last,
        ),
        if (scheduleId != null) 'schedule_id': scheduleId,
        if (latitude != null) 'latitude': latitude, // ✅ KIRIM GPS
        if (longitude != null) 'longitude': longitude, // ✅ KIRIM GPS
      });

      AppLogger.debug('📤 Sending check-in request...');

      // ✅ Kirim request dengan header X-Source: mobile-app
      final response = await _api.post(
        '/attendance/check-in',
        data: formData,
        headers: _mobileHeaders, // ✅ TAMBAH HEADER
      );

      AppLogger.debug('═══════════════════════════════════════');
      AppLogger.debug('📦 CHECK-IN RESPONSE');
      AppLogger.debug('Status: ${response.statusCode}');
      AppLogger.debug('Data: ${response.data}');
      AppLogger.debug('═══════════════════════════════════════');

      if (response.statusCode == 201 || response.statusCode == 200) {
        final data = response.data['data'];

        // ✅ Log LBPH info
        if (data != null) {
          AppLogger.debug('LBPH Recognition Info:');
          AppLogger.debug('  User ID: ${data['user_id']}');
          AppLogger.debug('  Distance: ${data['distance']}');
          AppLogger.debug('  Confidence: ${data['confidence']}%');
          AppLogger.debug('  Method: ${data['method']}');
        }

        return {
          'success': true,
          'message': response.data['message'] ?? 'Check-in berhasil!',
          'data': response.data['data'],
        };
      } else {
        return {
          'success': false,
          'message': response.data['message'] ?? 'Check-in gagal',
        };
      }
    } on DioException catch (e) {
      AppLogger.error('❌ CHECK-IN ERROR (DioException)');
      AppLogger.error('Type: ${e.type}');
      AppLogger.error('Message: ${e.message}');
      AppLogger.error('Response: ${e.response?.data}');

      // ✅ Handle error dari backend (GPS validation failed, dll)
      if (e.response?.statusCode == 400) {
        final errorData = e.response?.data;
        return {
          'success': false,
          'message': errorData['message'] ?? 'Check-in gagal',
          'data': errorData['data'], // Info jarak, radius, dll
        };
      }

      return {
        'success': false,
        'message': _api.handleError(e),
      };
    } catch (e) {
      AppLogger.error('❌ CHECK-IN ERROR (General)');
      AppLogger.error('Error: $e');

      return {
        'success': false,
        'message': 'Terjadi kesalahan: ${e.toString()}',
      };
    }
  }

  /// ✅ CHECK-OUT dengan GPS
  Future<Map<String, dynamic>> checkOut({
    required String photoPath,
    double? latitude, // ✅ GPS BARU
    double? longitude, // ✅ GPS BARU
  }) async {
    try {
      AppLogger.debug('═══════════════════════════════════════');
      AppLogger.debug('📸 CHECK-OUT SERVICE START');
      AppLogger.debug('═══════════════════════════════════════');
      AppLogger.debug('Photo Path: $photoPath');
      AppLogger.debug('Latitude: $latitude');
      AppLogger.debug('Longitude: $longitude');
      AppLogger.debug('═══════════════════════════════════════');

      // ✅ Prepare FormData
      final formData = FormData.fromMap({
        'photo': await MultipartFile.fromFile(
          photoPath,
          filename: photoPath.split('/').last,
        ),
        if (latitude != null) 'latitude': latitude, // ✅ KIRIM GPS
        if (longitude != null) 'longitude': longitude, // ✅ KIRIM GPS
      });

      AppLogger.debug('📤 Sending check-out request...');

      // ✅ Kirim request dengan header X-Source: mobile-app
      final response = await _api.post(
        '/attendance/check-out',
        data: formData,
        headers: _mobileHeaders, // ✅ TAMBAH HEADER
      );

      AppLogger.debug('═══════════════════════════════════════');
      AppLogger.debug('📦 CHECK-OUT RESPONSE');
      AppLogger.debug('Status: ${response.statusCode}');
      AppLogger.debug('Data: ${response.data}');
      AppLogger.debug('═══════════════════════════════════════');

      if (response.statusCode == 200) {
        return {
          'success': true,
          'message': response.data['message'] ?? 'Check-out berhasil!',
          'data': response.data['data'],
        };
      } else {
        return {
          'success': false,
          'message': response.data['message'] ?? 'Check-out gagal',
        };
      }
    } on DioException catch (e) {
      AppLogger.error('❌ CHECK-OUT ERROR (DioException)');
      AppLogger.error('Type: ${e.type}');
      AppLogger.error('Message: ${e.message}');
      AppLogger.error('Response: ${e.response?.data}');

      // ✅ Handle error dari backend (GPS validation failed, dll)
      if (e.response?.statusCode == 400) {
        final errorData = e.response?.data;
        return {
          'success': false,
          'message': errorData['message'] ?? 'Check-out gagal',
          'data': errorData['data'], // Info jarak, radius, dll
        };
      }

      return {
        'success': false,
        'message': _api.handleError(e),
      };
    } catch (e) {
      AppLogger.error('❌ CHECK-OUT ERROR (General)');
      AppLogger.error('Error: $e');

      return {
        'success': false,
        'message': 'Terjadi kesalahan: ${e.toString()}',
      };
    }
  }

  /// ✅ GET TODAY ATTENDANCE
  Future<Attendance?> getTodayAttendance() async {
    try {
      AppLogger.debug('📅 Getting today attendance...');

      final response = await _api.get('/attendance/today');

      AppLogger.debug('Response: ${response.data}');

      if (response.data['success'] == true && response.data['data'] != null) {
        return Attendance.fromJson(response.data['data']);
      }

      return null;
    } catch (e) {
      AppLogger.error('Error getting today attendance: $e');
      return null;
    }
  }

  /// ✅ GET MY ATTENDANCE (dengan filter tanggal)
  Future<List<Attendance>> getMyAttendance({
    String? startDate,
    String? endDate,
  }) async {
    try {
      AppLogger.debug('📋 Getting my attendance...');
      AppLogger.debug('Date range: $startDate to $endDate');

      final queryParams = <String, dynamic>{};
      if (startDate != null) queryParams['start_date'] = startDate;
      if (endDate != null) queryParams['end_date'] = endDate;

      final response = await _api.get(
        '/attendance/my',
        queryParameters: queryParams,
      );

      AppLogger.debug('Response: ${response.data}');

      if (response.data['success'] == true) {
        final List<dynamic> data = response.data['data'] ?? [];
        return data.map((json) => Attendance.fromJson(json)).toList();
      }

      return [];
    } catch (e) {
      AppLogger.error('Error getting my attendance: $e');
      return [];
    }
  }

  /// ✅ GET STATISTICS
  Future<Map<String, dynamic>?> getStatistics({
    String? startDate,
    String? endDate,
  }) async {
    try {
      AppLogger.debug('📊 Getting statistics...');

      final queryParams = <String, dynamic>{};
      if (startDate != null) queryParams['start_date'] = startDate;
      if (endDate != null) queryParams['end_date'] = endDate;

      final response = await _api.get(
        '/attendance/statistics',
        queryParameters: queryParams,
      );

      AppLogger.debug('Response: ${response.data}');

      if (response.data['success'] == true) {
        return response.data['data'];
      }

      return null;
    } catch (e) {
      AppLogger.error('Error getting statistics: $e');
      return null;
    }
  }
}
