import 'api_service.dart';

import '../models/report_model.dart';

// ============================================
// Report Service (Pimpinan only)
// ============================================
class ReportService {
  final ApiService _api = ApiService();

  // Get all reports
  Future<List<Report>> getReports({String? type, String? status, int page = 1}) async {
    try {
      final queryParams = <String, dynamic>{
        'page': page,
        if (type != null) 'type': type,
        if (status != null) 'status': status,
      };

      final response = await _api.get('/reports', queryParameters: queryParams);

      if (response.statusCode == 200 && response.data['success']) {
        final data = response.data['data']['data'] as List;
        return data.map((json) => Report.fromJson(json)).toList();
      }
      return [];
    } catch (e) {
      throw Exception(_api.handleError(e));
    }
  }

  // Generate new report
  Future<Report?> generateReport({
    required String title,
    required String type,
    required String startDate,
    required String endDate,
    required String format,
    List<int>? userIds,
    List<String>? departments,
  }) async {
    try {
      final response = await _api.post('/reports/generate', data: {
        'title': title,
        'type': type,
        'start_date': startDate,
        'end_date': endDate,
        'format': format,
        if (userIds != null) 'user_ids': userIds,
        if (departments != null) 'departments': departments,
      });

      if (response.statusCode == 201 && response.data['success']) {
        return Report.fromJson(response.data['data']);
      }
      return null;
    } catch (e) {
      throw Exception(_api.handleError(e));
    }
  }

  // Get report summary
  Future<Map<String, dynamic>?> getSummary({String? startDate, String? endDate}) async {
    try {
      final queryParams = <String, dynamic>{};
      if (startDate != null) queryParams['start_date'] = startDate;
      if (endDate != null) queryParams['end_date'] = endDate;

      final response = await _api.get('/reports/summary', queryParameters: queryParams);

      if (response.statusCode == 200 && response.data['success']) {
        return response.data['data'];
      }
      return null;
    } catch (e) {
      throw Exception(_api.handleError(e));
    }
  }

  // Get download URL
  Future<String?> getDownloadUrl(int reportId) async {
    try {
      final response = await _api.get('/reports/$reportId/download');

      if (response.statusCode == 200 && response.data['success']) {
        return response.data['data']['download_url'];
      }
      return null;
    } catch (e) {
      throw Exception(_api.handleError(e));
    }
  }
}
