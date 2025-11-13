// notification_service.dart
import 'api_service.dart';

// ============================================
// Notification Service
// ============================================
class NotificationService {
  final ApiService _api = ApiService();

  // Get all notifications
  Future<List<Map<String, dynamic>>> getNotifications({
    bool? isRead,
    String? type,
    int page = 1,
  }) async {
    try {
      final queryParams = <String, dynamic>{
        'page': page,
        if (isRead != null) 'is_read': isRead ? '1' : '0',
        if (type != null) 'type': type,
      };

      final response = await _api.get('/notifications', queryParameters: queryParams);

      if (response.statusCode == 200 && response.data['success']) {
        return List<Map<String, dynamic>>.from(response.data['data']['data']);
      }
      return [];
    } catch (e) {
      throw Exception(_api.handleError(e));
    }
  }

  // Get unread count
  Future<int> getUnreadCount() async {
    try {
      final response = await _api.get('/notifications/unread-count');

      if (response.statusCode == 200 && response.data['success']) {
        return response.data['data']['count'] ?? 0;
      }
      return 0;
    } catch (e) {
      return 0;
    }
  }

  // Mark as read
  Future<bool> markAsRead(int notificationId) async {
    try {
      final response = await _api.post('/notifications/$notificationId/mark-read');
      return response.statusCode == 200 && response.data['success'];
    } catch (e) {
      return false;
    }
  }

  // Mark all as read
  Future<bool> markAllAsRead() async {
    try {
      final response = await _api.post('/notifications/mark-all-read');
      return response.statusCode == 200 && response.data['success'];
    } catch (e) {
      return false;
    }
  }

  // Delete notification
  Future<bool> deleteNotification(int notificationId) async {
    try {
      final response = await _api.delete('/notifications/$notificationId');
      return response.statusCode == 200 && response.data['success'];
    } catch (e) {
      return false;
    }
  }
}