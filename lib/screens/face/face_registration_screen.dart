import 'package:flutter/material.dart';
import 'package:camera/camera.dart';
import '../../services/camera_service.dart';
import '../../services/face_recognition_service.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';
import '../../config/app_config.dart';
import '../../services/storage_service.dart';

class FaceRegistrationScreen extends StatefulWidget {
  final int userId;

  const FaceRegistrationScreen({super.key, required this.userId});

  @override
  State<FaceRegistrationScreen> createState() => _FaceRegistrationScreenState();
}

class _FaceRegistrationScreenState extends State<FaceRegistrationScreen> {
  final CameraService _cameraService = CameraService();
  final FaceRecognitionService _faceService = FaceRecognitionService();
  final StorageService _storage = StorageService();

  bool _isInitialized = false;
  bool _isProcessing = false;
  int _sampleCount = 0;
  final int _requiredSamples = 3;
  final List<String> _capturedEmbeddings = [];

  @override
  void initState() {
    super.initState();
    _initializeCamera();
  }

  Future<void> _initializeCamera() async {
    try {
      await _cameraService.initialize();
      setState(() => _isInitialized = true);
    } catch (e) {
      _showError('Gagal mengakses kamera: $e');
    }
  }

  @override
  void dispose() {
    _cameraService.dispose();
    _faceService.dispose();
    super.dispose();
  }

  Future<void> _captureSample() async {
    if (_isProcessing) return;

    setState(() => _isProcessing = true);

    try {
      final imagePath = await _cameraService.takePicture();
      if (imagePath == null) throw Exception('Gagal mengambil foto');

      // Extract embedding
      final embedding = await _faceService.extractFaceEmbedding(imagePath);
      final quality = await _faceService.getFaceQualityScore(imagePath);

      if (quality < 0.7) {
        throw Exception('Kualitas foto kurang baik. Silakan coba lagi.');
      }

      // Save to server
      await _uploadFaceData(imagePath, embedding, quality);

      _capturedEmbeddings.add(embedding.join(','));
      setState(() => _sampleCount++);

      _showSuccess('Sample $_sampleCount/$_requiredSamples berhasil diambil');

      if (_sampleCount >= _requiredSamples) {
        await Future.delayed(const Duration(seconds: 1));
        Navigator.pop(context, true);
      }
    } catch (e) {
      _showError(e.toString());
    } finally {
      setState(() => _isProcessing = false);
    }
  }

  Future<void> _uploadFaceData(
      String imagePath, List<double> embedding, double quality) async {
    final token = _storage.getString('token');
    final uri = Uri.parse('${AppConfig.baseUrl}/face-data');

    // Gunakan jsonEncode dari 'dart:convert' untuk debug/logging
    final jsonData = jsonEncode({
      'user_id': widget.userId,
      'embedding': embedding,
      'quality': quality,
    });
    debugPrint('Mengirim data wajah ke server: $jsonData');

    final request = http.MultipartRequest('POST', uri)
      ..headers['Authorization'] = 'Bearer $token'
      ..fields['user_id'] = widget.userId.toString()
      ..fields['face_encoding'] = embedding.join(',')
      ..fields['quality_score'] = quality.toString()
      ..files.add(await http.MultipartFile.fromPath('photo', imagePath));

    final response = await request.send();

    if (response.statusCode != 201) {
      throw Exception('Gagal menyimpan data wajah');
    }
  }

  void _showError(String message) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(message), backgroundColor: Colors.red),
    );
  }

  void _showSuccess(String message) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(message), backgroundColor: Colors.green),
    );
  }

  @override
  Widget build(BuildContext context) {
    final progress = _sampleCount / _requiredSamples;

    return Scaffold(
      appBar: AppBar(title: const Text('Registrasi Wajah')),
      body: !_isInitialized
          ? const Center(child: CircularProgressIndicator())
          : Stack(
              children: [
                Positioned.fill(
                    child: CameraPreview(_cameraService.controller!)),

                // Header
                Positioned(
                  top: 0,
                  left: 0,
                  right: 0,
                  child: Container(
                    padding: const EdgeInsets.all(16),
                    color: Colors.black54,
                    child: Column(
                      children: [
                        Text(
                          'Sample $_sampleCount/$_requiredSamples',
                          style: const TextStyle(
                              color: Colors.white,
                              fontSize: 18,
                              fontWeight: FontWeight.bold),
                        ),
                        const SizedBox(height: 8),
                        LinearProgressIndicator(value: progress, minHeight: 6),
                        const SizedBox(height: 8),
                        const Text(
                          'Ambil foto dari sudut berbeda',
                          style: TextStyle(color: Colors.white70),
                        ),
                      ],
                    ),
                  ),
                ),

                // Capture button
                Positioned(
                  bottom: 40,
                  left: 0,
                  right: 0,
                  child: Center(
                    child: GestureDetector(
                      onTap: _captureSample,
                      child: Container(
                        width: 70,
                        height: 70,
                        decoration: BoxDecoration(
                          shape: BoxShape.circle,
                          color: _isProcessing ? Colors.grey : Colors.white,
                          border: Border.all(color: Colors.white, width: 4),
                        ),
                        child: _isProcessing
                            ? const Padding(
                                padding: EdgeInsets.all(16),
                                child:
                                    CircularProgressIndicator(strokeWidth: 3),
                              )
                            : const Icon(Icons.camera, size: 35),
                      ),
                    ),
                  ),
                ),
              ],
            ),
    );
  }
}
