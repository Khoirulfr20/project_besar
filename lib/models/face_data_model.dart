// ============================================
// Face Data Model
// ============================================
class FaceData {
  final int id;
  final int userId;
  final String faceEncoding;
  final String facePhoto;
  final int faceSampleNumber;
  final double? qualityScore;
  final bool isPrimary;
  final bool isActive;
  final DateTime createdAt;

  FaceData({
    required this.id,
    required this.userId,
    required this.faceEncoding,
    required this.facePhoto,
    required this.faceSampleNumber,
    this.qualityScore,
    required this.isPrimary,
    required this.isActive,
    required this.createdAt,
  });

  factory FaceData.fromJson(Map<String, dynamic> json) {
    return FaceData(
      id: json['id'],
      userId: json['user_id'],
      faceEncoding: json['face_encoding'],
      facePhoto: json['face_photo'],
      faceSampleNumber: json['face_sample_number'],
      qualityScore: json['quality_score']?.toDouble(),
      isPrimary: json['is_primary'] ?? false,
      isActive: json['is_active'] ?? true,
      createdAt: DateTime.parse(json['created_at']),
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'user_id': userId,
      'face_encoding': faceEncoding,
      'face_photo': facePhoto,
      'face_sample_number': faceSampleNumber,
      'quality_score': qualityScore,
      'is_primary': isPrimary,
      'is_active': isActive,
    };
  }

  String get qualityScoreFormatted {
    if (qualityScore == null) return '-';
    return '${(qualityScore! * 100).toStringAsFixed(0)}%';
  }
}

// ============================================
// Setting Model
// ============================================
class Setting {
  final int id;
  final String key;
  final String value;
  final String type;
  final String group;
  final String? description;
  final bool isPublic;

  Setting({
    required this.id,
    required this.key,
    required this.value,
    required this.type,
    required this.group,
    this.description,
    required this.isPublic,
  });

  factory Setting.fromJson(Map<String, dynamic> json) {
    return Setting(
      id: json['id'],
      key: json['key'],
      value: json['value'].toString(),
      type: json['type'],
      group: json['group'],
      description: json['description'],
      isPublic: json['is_public'] ?? false,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'key': key,
      'value': value,
      'type': type,
      'group': group,
      'description': description,
      'is_public': isPublic,
    };
  }

  dynamic get castedValue {
    switch (type) {
      case 'boolean':
        return value.toLowerCase() == 'true' || value == '1';
      case 'integer':
        return int.tryParse(value) ?? 0;
      case 'float':
        return double.tryParse(value) ?? 0.0;
      default:
        return value;
    }
  }
}

// ============================================
// API Response Model
// ============================================
class ApiResponse<T> {
  final bool success;
  final String? message;
  final T? data;
  final Map<String, dynamic>? errors;

  ApiResponse({
    required this.success,
    this.message,
    this.data,
    this.errors,
  });

  factory ApiResponse.fromJson(
    Map<String, dynamic> json,
    T Function(dynamic)? fromJsonT,
  ) {
    return ApiResponse(
      success: json['success'] ?? false,
      message: json['message'],
      data: json['data'] != null && fromJsonT != null ? fromJsonT(json['data']) : null,
      errors: json['errors'],
    );
  }
}

