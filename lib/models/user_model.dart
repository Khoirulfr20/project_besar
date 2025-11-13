import 'dart:convert';

class User {
  final int id;
  final String employeeId;
  final String name;
  final String email;
  final String role;
  final String? phone;
  final String? position;
  final String? department;
  final String? photo;
  final bool isActive;
  final bool hasFaceData;

  User({
    required this.id,
    required this.employeeId,
    required this.name,
    required this.email,
    required this.role,
    this.phone,
    this.position,
    this.department,
    this.photo,
    required this.isActive,
    this.hasFaceData = false,
  });

  factory User.fromJson(Map<String, dynamic> json) {
    return User(
      id: json['id'],
      employeeId: json['employee_id'],
      name: json['name'],
      email: json['email'],
      role: json['role'],
      phone: json['phone'],
      position: json['position'],
      department: json['department'],
      photo: json['photo'],
      isActive: json['is_active'] ?? true,
      hasFaceData: json['has_face_data'] ?? false,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'employee_id': employeeId,
      'name': name,
      'email': email,
      'role': role,
      'phone': phone,
      'position': position,
      'department': department,
      'photo': photo,
      'is_active': isActive,
      'has_face_data': hasFaceData,
    };
  }

  String toJsonString() => json.encode(toJson());

  factory User.fromJsonString(String jsonString) {
    return User.fromJson(json.decode(jsonString));
  }

  bool get isAdmin => role == 'admin';
  bool get isPimpinan => role == 'pimpinan';
  bool get isAnggota => role == 'anggota';

  User copyWith({
    int? id,
    String? employeeId,
    String? name,
    String? email,
    String? role,
    String? phone,
    String? position,
    String? department,
    String? photo,
    bool? isActive,
    bool? hasFaceData,
  }) {
    return User(
      id: id ?? this.id,
      employeeId: employeeId ?? this.employeeId,
      name: name ?? this.name,
      email: email ?? this.email,
      role: role ?? this.role,
      phone: phone ?? this.phone,
      position: position ?? this.position,
      department: department ?? this.department,
      photo: photo ?? this.photo,
      isActive: isActive ?? this.isActive,
      hasFaceData: hasFaceData ?? this.hasFaceData,
    );
  }
}
