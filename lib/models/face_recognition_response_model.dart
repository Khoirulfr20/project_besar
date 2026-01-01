class FaceRecognitionResponse {
  final bool success;
  final String message;
  final FaceRecognitionData? data;

  FaceRecognitionResponse({
    required this.success,
    required this.message,
    this.data,
  });

  factory FaceRecognitionResponse.fromJson(Map<String, dynamic> json) {
    return FaceRecognitionResponse(
      success: json['success'] ?? false,
      message: json['message'] ?? '',
      data: json['data'] != null 
          ? FaceRecognitionData.fromJson(json['data']) 
          : null,
    );
  }
}

class FaceRecognitionData {
  final int userId;
  final double distance;       // ✅ LBPH distance
  final double confidence;     // ✅ Confidence percentage (0-100)
  final double threshold;
  final String method;         // ✅ "LBPH"

  FaceRecognitionData({
    required this.userId,
    required this.distance,
    required this.confidence,
    required this.threshold,
    required this.method,
  });

  factory FaceRecognitionData.fromJson(Map<String, dynamic> json) {
    return FaceRecognitionData(
      userId: json['user_id'] is String 
          ? int.parse(json['user_id']) 
          : json['user_id'],
      distance: (json['distance'] is int)
          ? (json['distance'] as int).toDouble()
          : json['distance'].toDouble(),
      confidence: (json['confidence'] is int)
          ? (json['confidence'] as int).toDouble()
          : json['confidence'].toDouble(),
      threshold: (json['threshold'] is int)
          ? (json['threshold'] as int).toDouble()
          : json['threshold'].toDouble(),
      method: json['method'] ?? 'LBPH',
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'user_id': userId,
      'distance': distance,
      'confidence': confidence,
      'threshold': threshold,
      'method': method,
    };
  }

  /// ✅ Helper: Check if match is good
  bool get isGoodMatch => distance <= threshold;
  
  /// ✅ Helper: Get quality label
  String get qualityLabel {
    if (distance <= 20) return 'Excellent';
    if (distance <= 30) return 'Good';
    if (distance <= 40) return 'Fair';
    return 'Poor';
  }

  /// ✅ Helper: Get confidence color
  String get confidenceColor {
    if (confidence >= 80) return 'green';
    if (confidence >= 60) return 'orange';
    return 'red';
  }
}