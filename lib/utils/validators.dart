
class Validators {
  // Email validation
  static String? email(String? value) {
    if (value == null || value.isEmpty) {
      return 'Email tidak boleh kosong';
    }
    
    final emailRegex = RegExp(r'^[\w-\.]+@([\w-]+\.)+[\w-]{2,4}$');
    if (!emailRegex.hasMatch(value)) {
      return 'Format email tidak valid';
    }
    
    return null;
  }

  // Password validation
  static String? password(String? value, {int minLength = 6}) {
    if (value == null || value.isEmpty) {
      return 'Password tidak boleh kosong';
    }
    
    if (value.length < minLength) {
      return 'Password minimal $minLength karakter';
    }
    
    return null;
  }

  // Required field validation
  static String? required(String? value, {String? fieldName}) {
    if (value == null || value.isEmpty) {
      return '${fieldName ?? 'Field'} tidak boleh kosong';
    }
    return null;
  }

  // Phone number validation
  static String? phone(String? value) {
    if (value == null || value.isEmpty) {
      return 'Nomor telepon tidak boleh kosong';
    }
    
    final phoneRegex = RegExp(r'^[0-9]{10,13}$');
    if (!phoneRegex.hasMatch(value)) {
      return 'Format nomor telepon tidak valid';
    }
    
    return null;
  }

  // Employee ID validation
  static String? employeeId(String? value) {
    if (value == null || value.isEmpty) {
      return 'ID Karyawan tidak boleh kosong';
    }
    
    if (value.length < 3) {
      return 'ID Karyawan minimal 3 karakter';
    }
    
    return null;
  }

  // Confirm password validation
  static String? confirmPassword(String? value, String? password) {
    if (value == null || value.isEmpty) {
      return 'Konfirmasi password tidak boleh kosong';
    }
    
    if (value != password) {
      return 'Password tidak cocok';
    }
    
    return null;
  }

  // Number validation
  static String? number(String? value, {String? fieldName}) {
    if (value == null || value.isEmpty) {
      return '${fieldName ?? 'Angka'} tidak boleh kosong';
    }
    
    if (int.tryParse(value) == null) {
      return '${fieldName ?? 'Field'} harus berupa angka';
    }
    
    return null;
  }

  // Min length validation
  static String? minLength(String? value, int min, {String? fieldName}) {
    if (value == null || value.isEmpty) {
      return '${fieldName ?? 'Field'} tidak boleh kosong';
    }
    
    if (value.length < min) {
      return '${fieldName ?? 'Field'} minimal $min karakter';
    }
    
    return null;
  }

  // Max length validation
  static String? maxLength(String? value, int max, {String? fieldName}) {
    if (value == null) return null;
    
    if (value.length > max) {
      return '${fieldName ?? 'Field'} maksimal $max karakter';
    }
    
    return null;
  }

  // URL validation
  static String? url(String? value) {
    if (value == null || value.isEmpty) {
      return 'URL tidak boleh kosong';
    }
    
    final urlRegex = RegExp(
      r'^https?:\/\/(www\.)?[-a-zA-Z0-9@:%._\+~#=]{1,256}\.[a-zA-Z0-9()]{1,6}\b([-a-zA-Z0-9()@:%_\+.~#?&//=]*)$'
    );
    
    if (!urlRegex.hasMatch(value)) {
      return 'Format URL tidak valid';
    }
    
    return null;
  }

  // Time validation (HH:mm format)
  static String? time(String? value) {
    if (value == null || value.isEmpty) {
      return 'Waktu tidak boleh kosong';
    }
    
    final timeRegex = RegExp(r'^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$');
    if (!timeRegex.hasMatch(value)) {
      return 'Format waktu tidak valid (HH:mm)';
    }
    
    return null;
  }

  // Date validation (yyyy-MM-dd format)
  static String? date(String? value) {
    if (value == null || value.isEmpty) {
      return 'Tanggal tidak boleh kosong';
    }
    
    try {
      DateTime.parse(value);
      return null;
    } catch (e) {
      return 'Format tanggal tidak valid';
    }
  }
}