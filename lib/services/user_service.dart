import 'api_service.dart';
import '../models/user_model.dart';

// User Service (Admin only)
// ============================================
class UserService {
  final ApiService _api = ApiService();

  // Get all users
  Future<List<User>> getUsers({
    String? role,
    String? department,
    bool? isActive,
    String? search,
    int page = 1,
  }) async {
    try {
      final queryParams = <String, dynamic>{
        'page': page,
        if (role != null) 'role': role,
        if (department != null) 'department': department,
        if (isActive != null) 'is_active': isActive ? '1' : '0',
        if (search != null) 'search': search,
      };

      final response = await _api.get('/users', queryParameters: queryParams);

      if (response.statusCode == 200 && response.data['success']) {
        final data = response.data['data']['data'] as List;
        return data.map((json) => User.fromJson(json)).toList();
      }
      return [];
    } catch (e) {
      throw Exception(_api.handleError(e));
    }
  }

  // Get user by ID
  Future<User?> getUserById(int id) async {
    try {
      final response = await _api.get('/users/$id');

      if (response.statusCode == 200 && response.data['success']) {
        return User.fromJson(response.data['data']);
      }
      return null;
    } catch (e) {
      throw Exception(_api.handleError(e));
    }
  }

  // Create user
  Future<User?> createUser({
    required String employeeId,
    required String name,
    required String email,
    required String password,
    required String role,
    String? phone,
    String? position,
    String? department,
    String? photoPath,
  }) async {
    try {
      Map<String, dynamic> data = {
        'employee_id': employeeId,
        'name': name,
        'email': email,
        'password': password,
        'role': role,
        if (phone != null) 'phone': phone,
        if (position != null) 'position': position,
        if (department != null) 'department': department,
      };

      final response = photoPath != null
          ? await _api.uploadFile('/users', filePath: photoPath, fieldName: 'photo', data: data)
          : await _api.post('/users', data: data);

      if (response.statusCode == 201 && response.data['success']) {
        return User.fromJson(response.data['data']);
      }
      return null;
    } catch (e) {
      throw Exception(_api.handleError(e));
    }
  }

  // Update user
  Future<User?> updateUser({
    required int id,
    String? employeeId,
    String? name,
    String? email,
    String? password,
    String? role,
    String? phone,
    String? position,
    String? department,
    String? photoPath,
    bool? isActive,
  }) async {
    try {
      Map<String, dynamic> data = {};
      if (employeeId != null) data['employee_id'] = employeeId;
      if (name != null) data['name'] = name;
      if (email != null) data['email'] = email;
      if (password != null) data['password'] = password;
      if (role != null) data['role'] = role;
      if (phone != null) data['phone'] = phone;
      if (position != null) data['position'] = position;
      if (department != null) data['department'] = department;
      if (isActive != null) data['is_active'] = isActive ? 1 : 0;

      final response = photoPath != null
          ? await _api.uploadFile('/users/$id', filePath: photoPath, fieldName: 'photo', data: data)
          : await _api.put('/users/$id', data: data);

      if (response.statusCode == 200 && response.data['success']) {
        return User.fromJson(response.data['data']);
      }
      return null;
    } catch (e) {
      throw Exception(_api.handleError(e));
    }
  }

  // Delete user
  Future<bool> deleteUser(int id) async {
    try {
      final response = await _api.delete('/users/$id');
      return response.statusCode == 200 && response.data['success'];
    } catch (e) {
      throw Exception(_api.handleError(e));
    }
  }

  // Toggle user status
  Future<bool> toggleStatus(int id) async {
    try {
      final response = await _api.post('/users/$id/toggle-status');
      return response.statusCode == 200 && response.data['success'];
    } catch (e) {
      throw Exception(_api.handleError(e));
    }
  }
}