import 'package:flutter/material.dart';
import 'package:camera/camera.dart';
import 'package:provider/provider.dart';
import '../../services/camera_service.dart';
import '../../services/face_recognition_service.dart';
import '../../providers/attendance_provider.dart';
// ignore: unused_import
import '../../widgets/camera/face_detection_painter.dart';

class FaceCaptureScreen extends StatefulWidget {
  final bool isCheckOut;

  const FaceCaptureScreen({super.key, this.isCheckOut = false});

  @override
  State<FaceCaptureScreen> createState() => _FaceCaptureScreenState();
}

class _FaceCaptureScreenState extends State<FaceCaptureScreen> {
  final CameraService _cameraService = CameraService();
  final FaceRecognitionService _faceService = FaceRecognitionService();

  bool _isInitialized = false;
  bool _isProcessing = false;
  String _statusMessage = 'Posisikan wajah Anda';

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

  Future<void> _captureAndVerify() async {
    if (_isProcessing) return;

    setState(() {
      _isProcessing = true;
      _statusMessage = 'Memproses...';
    });

    try {
      // Take picture
      final imagePath = await _cameraService.takePicture();
      if (imagePath == null) {
        throw Exception('Gagal mengambil foto');
      }

      // Detect faces
      final faces = await _faceService.detectFaces(imagePath);

      if (faces.isEmpty) {
        throw Exception('Tidak ada wajah terdeteksi');
      }

      if (faces.length > 1) {
        throw Exception('Terdeteksi lebih dari satu wajah');
      }

      // Extract embedding
      await _faceService.extractFaceEmbedding(imagePath);

      // Get quality score
      final quality = await _faceService.getFaceQualityScore(imagePath);

      if (quality < 0.6) {
        throw Exception(
            'Kualitas foto kurang baik. Pastikan pencahayaan cukup dan wajah terlihat jelas.');
      }

      // In real app, you would verify against stored embeddings here
      // For demo, we'll simulate with random confidence
      const confidence = 0.85; // Simulated confidence

      // Submit attendance
      final attendanceProvider =
          Provider.of<AttendanceProvider>(context, listen: false);

      Map<String, dynamic> result;
      if (widget.isCheckOut) {
        result = await attendanceProvider.checkOut(
          photoPath: imagePath,
          confidence: confidence,
        );
      } else {
        result = await attendanceProvider.checkIn(
          photoPath: imagePath,
          confidence: confidence,
        );
      }

      if (result['success']) {
        _showSuccess(result['message']);
        await Future.delayed(const Duration(seconds: 2));
        Navigator.pop(context, true);
      } else {
        throw Exception(result['message']);
      }
    } catch (e) {
      _showError(e.toString());
    } finally {
      setState(() {
        _isProcessing = false;
        _statusMessage = 'Posisikan wajah Anda';
      });
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
    return Scaffold(
      appBar: AppBar(
        title: Text(widget.isCheckOut ? 'Check-Out' : 'Check-In'),
      ),
      body: !_isInitialized
          ? const Center(child: CircularProgressIndicator())
          : Stack(
              children: [
                // Camera preview
                Positioned.fill(
                  child: CameraPreview(_cameraService.controller!),
                ),

                // Face guide overlay
                Positioned.fill(
                  child: CustomPaint(
                    painter: FaceGuidePainter(),
                  ),
                ),

                // Status bar
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
                          _statusMessage,
                          style: const TextStyle(
                            color: Colors.white,
                            fontSize: 16,
                            fontWeight: FontWeight.bold,
                          ),
                          textAlign: TextAlign.center,
                        ),
                        const SizedBox(height: 8),
                        const Text(
                          'Pastikan wajah Anda berada di dalam lingkaran',
                          style: TextStyle(color: Colors.white70, fontSize: 12),
                          textAlign: TextAlign.center,
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
                      onTap: _captureAndVerify,
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

                // Tips
                Positioned(
                  bottom: 140,
                  left: 16,
                  right: 16,
                  child: Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: Colors.black54,
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: const Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'Tips:',
                          style: TextStyle(
                            color: Colors.white,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                        SizedBox(height: 4),
                        Text(
                          '• Pastikan pencahayaan cukup\n'
                          '• Lihat langsung ke kamera\n'
                          '• Jangan gunakan masker/kacamata hitam',
                          style: TextStyle(color: Colors.white70, fontSize: 12),
                        ),
                      ],
                    ),
                  ),
                ),
              ],
            ),
    );
  }
}

// Face guide painter
class FaceGuidePainter extends CustomPainter {
  @override
  void paint(Canvas canvas, Size size) {
    final paint = Paint()
      ..color = Colors.white
      ..style = PaintingStyle.stroke
      ..strokeWidth = 3;

    final center = Offset(size.width / 2, size.height / 2 - 50);
    final radius = size.width * 0.35;

    // Draw oval guide
    final rect = Rect.fromCenter(
      center: center,
      width: radius * 2,
      height: radius * 2.3,
    );

    canvas.drawOval(rect, paint);

    // Draw corner guides
    final cornerPaint = Paint()
      ..color = Colors.white
      ..style = PaintingStyle.stroke
      ..strokeWidth = 4;

    const cornerLength = 30.0;

    // Top-left
    canvas.drawLine(
      Offset(rect.left, rect.top),
      Offset(rect.left + cornerLength, rect.top),
      cornerPaint,
    );
    canvas.drawLine(
      Offset(rect.left, rect.top),
      Offset(rect.left, rect.top + cornerLength),
      cornerPaint,
    );

    // Top-right
    canvas.drawLine(
      Offset(rect.right, rect.top),
      Offset(rect.right - cornerLength, rect.top),
      cornerPaint,
    );
    canvas.drawLine(
      Offset(rect.right, rect.top),
      Offset(rect.right, rect.top + cornerLength),
      cornerPaint,
    );

    // Bottom-left
    canvas.drawLine(
      Offset(rect.left, rect.bottom),
      Offset(rect.left + cornerLength, rect.bottom),
      cornerPaint,
    );
    canvas.drawLine(
      Offset(rect.left, rect.bottom),
      Offset(rect.left, rect.bottom - cornerLength),
      cornerPaint,
    );

    // Bottom-right
    canvas.drawLine(
      Offset(rect.right, rect.bottom),
      Offset(rect.right - cornerLength, rect.bottom),
      cornerPaint,
    );
    canvas.drawLine(
      Offset(rect.right, rect.bottom),
      Offset(rect.right, rect.bottom - cornerLength),
      cornerPaint,
    );
  }

  @override
  bool shouldRepaint(covariant CustomPainter oldDelegate) => false;
}
