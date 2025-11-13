import 'package:flutter/material.dart';
import '../models/user_model.dart';
import '../services/auth_service.dart';



// ============================================
// Auth Provider
// ============================================
class AuthProvider with ChangeNotifier {
  final AuthService _authService = AuthService();
  User? _user;
  bool _isLoading = false;

  User? get user => _user;
  bool get isLoading => _isLoading;
  bool get isLoggedIn => _user != null;

  Future<Map<String, dynamic>> login(String email, String password) async {
    _isLoading = true;
    notifyListeners();

    final result = await _authService.login(email, password);
    
    if (result['success']) {
      _user = result['user'];
    }

    _isLoading = false;
    notifyListeners();
    return result;
  }

  Future<void> loadUser() async {
    _user = await _authService.getCurrentUser();
    notifyListeners();
  }

  Future<void> logout() async {
    await _authService.logout();
    _user = null;
    notifyListeners();
  }
}