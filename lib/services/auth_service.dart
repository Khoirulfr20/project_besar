// auth_service.dart
import 'package:shared_preferences/shared_preferences.dart';
import 'api_service.dart';
import '../models/user_model.dart';

class AuthService {
  final ApiService _api = ApiService();

  // Login
  Future<Map<String, dynamic>> login(String email, String password) async {
    try {
      final response = await _api.post(
        '/login',
        data: {'email': email, 'password': password},
      );

      if (response.statusCode == 200 && response.data['success']) {
        final data = response.data['data'];

        // Save token
        await _api.saveToken(data['token']);

        // Save user data
        await _saveUserData(data['user']);

        return {'success': true, 'user': User.fromJson(data['user'])};
      } else {
        return {
          'success': false,
          'message': response.data['message'] ?? 'Login gagal',
        };
      }
    } catch (e) {
      return {'success': false, 'message': _api.handleError(e)};
    }
  }

  // Get Current User
  Future<User?> getCurrentUser() async {
    try {
      final response = await _api.get('/me');

      if (response.statusCode == 200 && response.data['success']) {
        final userData = response.data['data'];
        await _saveUserData(userData);
        return User.fromJson(userData);
      }
      return null;
    } catch (e) {
      return null;
    }
  }

  // Logout
  Future<bool> logout() async {
    try {
      await _api.post('/logout');
      await _api.removeToken();
      await _clearUserData();
      return true;
    } catch (e) {
      // Clear local data even if API call fails
      await _api.removeToken();
      await _clearUserData();
      return true;
    }
  }

  // Check if logged in
  Future<bool> isLoggedIn() async {
    final token = await _api.getToken();
    return token != null && token.isNotEmpty;
  }

  // Get stored user data
  Future<User?> getStoredUser() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final userJson = prefs.getString('user');
      if (userJson != null) {
        return User.fromJsonString(userJson);
      }
      return null;
    } catch (e) {
      return null;
    }
  }

  // Private: Save user data locally
  Future<void> _saveUserData(Map<String, dynamic> userData) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('user', User.fromJson(userData).toJsonString());
  }

  // Private: Clear user data
  Future<void> _clearUserData() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove('user');
  }

}
