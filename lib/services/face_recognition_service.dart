// face_recognition_service.dart
import 'dart:math';
import 'package:google_mlkit_face_detection/google_mlkit_face_detection.dart';


class FaceRecognitionService {
  late FaceDetector _faceDetector;

  FaceRecognitionService() {
    final options = FaceDetectorOptions(
      enableLandmarks: true,
      enableContours: true,
      enableClassification: true,
      minFaceSize: 0.15,
      performanceMode: FaceDetectorMode.accurate,
    );
    _faceDetector = FaceDetector(options: options);
  }

  // Detect faces in image
  Future<List<Face>> detectFaces(String imagePath) async {
    final inputImage = InputImage.fromFilePath(imagePath);
    final faces = await _faceDetector.processImage(inputImage);
    return faces;
  }

  // Extract face embeddings (simplified version)
  Future<List<double>> extractFaceEmbedding(String imagePath) async {
    final faces = await detectFaces(imagePath);

    if (faces.isEmpty) {
      throw Exception('Tidak ada wajah terdeteksi');
    }

    if (faces.length > 1) {
      throw Exception('Terdeteksi lebih dari satu wajah');
    }

    final face = faces.first;

    // Check face quality
    if (!_isFaceQualityGood(face)) {
      throw Exception('Kualitas wajah tidak memenuhi standar');
    }

    // Create embedding from face landmarks
    return _createEmbedding(face, imagePath);
  }

  // Check face quality
  bool _isFaceQualityGood(Face face) {
    // Check if face is looking straight
    final headY = face.headEulerAngleY ?? 0;
    final headZ = face.headEulerAngleZ ?? 0;

    if (headY.abs() > 15 || headZ.abs() > 15) {
      return false; // Face not looking straight
    }

    // Check if eyes are open
    final leftEyeOpen = face.leftEyeOpenProbability ?? 0;
    final rightEyeOpen = face.rightEyeOpenProbability ?? 0;

    if (leftEyeOpen < 0.5 || rightEyeOpen < 0.5) {
      return false; // Eyes closed
    }

    // Check face size
    final boundingBox = face.boundingBox;
    if (boundingBox.width < 100 || boundingBox.height < 100) {
      return false; // Face too small
    }

    return true;
  }

  // Create face embedding from landmarks
  Future<List<double>> _createEmbedding(Face face, String imagePath) async {
    final embedding = <double>[];

    // Add bounding box features
    final box = face.boundingBox;
    embedding.addAll([
      box.left.toDouble(),
      box.top.toDouble(),
      box.width.toDouble(),
      box.height.toDouble(),
    ]);

    // Add head rotation features
    embedding.addAll([
      face.headEulerAngleX ?? 0,
      face.headEulerAngleY ?? 0,
      face.headEulerAngleZ ?? 0,
    ]);

    // Add landmark distances
    final landmarks = face.landmarks;
    if (landmarks.isNotEmpty) {
      final leftEye = landmarks[FaceLandmarkType.leftEye]?.position;
      final rightEye = landmarks[FaceLandmarkType.rightEye]?.position;
      final nose = landmarks[FaceLandmarkType.noseBase]?.position;
      final leftMouth = landmarks[FaceLandmarkType.leftMouth]?.position;
      final rightMouth = landmarks[FaceLandmarkType.rightMouth]?.position;

      if (leftEye != null && rightEye != null) {
        embedding.add(_calculateDistance(leftEye, rightEye));
      }
      if (leftEye != null && nose != null) {
        embedding.add(_calculateDistance(leftEye, nose));
      }
      if (rightEye != null && nose != null) {
        embedding.add(_calculateDistance(rightEye, nose));
      }
      if (leftMouth != null && rightMouth != null) {
        embedding.add(_calculateDistance(leftMouth, rightMouth));
      }
      if (nose != null && leftMouth != null) {
        embedding.add(_calculateDistance(nose, leftMouth));
      }
    }

    return embedding;
  }

  // Calculate euclidean distance
  double _calculateDistance(Point<int> p1, Point<int> p2) {
    final dx = p1.x - p2.x;
    final dy = p1.y - p2.y;
    return sqrt(dx * dx + dy * dy);
  }

  // Compare two face embeddings
  double compareFaces(List<double> embedding1, List<double> embedding2) {
    if (embedding1.length != embedding2.length) {
      return 0.0;
    }

    double sum = 0;
    for (int i = 0; i < embedding1.length; i++) {
      final diff = embedding1[i] - embedding2[i];
      sum += diff * diff;
    }

    final distance = sqrt(sum);
    // Convert distance to confidence (0-1)
    final confidence = 1 / (1 + distance / 1000);

    return confidence;
  }

  // Verify face against stored embeddings
  Future<Map<String, dynamic>> verifyFace(
    String imagePath,
    List<String> storedEmbeddings,
  ) async {
    try {
      final currentEmbedding = await extractFaceEmbedding(imagePath);

      double maxConfidence = 0.0;
      int bestMatchIndex = -1;

      for (int i = 0; i < storedEmbeddings.length; i++) {
        final storedEmbed = _parseEmbedding(storedEmbeddings[i]);
        final confidence = compareFaces(currentEmbedding, storedEmbed);

        if (confidence > maxConfidence) {
          maxConfidence = confidence;
          bestMatchIndex = i;
        }
      }

      return {
        'success': true,
        'confidence': maxConfidence,
        'matchIndex': bestMatchIndex,
        'embedding': _embeddingToString(currentEmbedding),
      };
    } catch (e) {
      return {'success': false, 'message': e.toString()};
    }
  }

  // Convert embedding to string for storage
  String _embeddingToString(List<double> embedding) {
    return embedding.join(',');
  }

  // Parse embedding from string
  List<double> _parseEmbedding(String embeddingString) {
    return embeddingString.split(',').map((e) => double.parse(e)).toList();
  }

  // Get face quality score
  Future<double> getFaceQualityScore(String imagePath) async {
    final faces = await detectFaces(imagePath);

    if (faces.isEmpty) return 0.0;

    final face = faces.first;
    double score = 1.0;

    // Penalize for head rotation
    final headY = (face.headEulerAngleY ?? 0).abs();
    final headZ = (face.headEulerAngleZ ?? 0).abs();
    score -= (headY + headZ) / 60; // Max penalty 0.5

    // Penalize for closed eyes
    final leftEye = face.leftEyeOpenProbability ?? 1.0;
    final rightEye = face.rightEyeOpenProbability ?? 1.0;
    score -= (2 - leftEye - rightEye) / 4; // Max penalty 0.5

    return score.clamp(0.0, 1.0);
  }

  void dispose() {
    _faceDetector.close();
  }

  Future fileToBytes(String imagePath) async {}
}
