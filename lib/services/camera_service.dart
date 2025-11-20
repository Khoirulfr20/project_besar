import 'dart:io';
import 'package:camera/camera.dart';
import 'package:path_provider/path_provider.dart';
import 'package:image/image.dart' as img;
import 'package:permission_handler/permission_handler.dart';

class CameraService {
  CameraController? _controller;
  List<CameraDescription>? _cameras;

  /// Initialize camera with permission check
  Future<void> initialize() async {
    // ✅ CEK PERMISSION DULU!
    final cameraStatus = await Permission.camera.status;

    if (cameraStatus.isDenied || cameraStatus.isPermanentlyDenied) {
      final result = await Permission.camera.request();

      if (!result.isGranted) {
        throw Exception(
            'Akses kamera ditolak. Silakan aktifkan di pengaturan aplikasi.');
      }
    }

    // Setelah permission granted, baru ambil cameras
    _cameras = await availableCameras();

    if (_cameras == null || _cameras!.isEmpty) {
      throw Exception('Tidak ada kamera yang tersedia');
    }

    // Use front camera for face recognition
    final frontCamera = _cameras!.firstWhere(
      (camera) => camera.lensDirection == CameraLensDirection.front,
      orElse: () => _cameras!.first,
    );

    _controller = CameraController(
      frontCamera,
      ResolutionPreset.high,
      enableAudio: false,
      imageFormatGroup: ImageFormatGroup.jpeg,
    );

    try {
      await _controller!.initialize();
    } catch (e) {
      throw Exception('Gagal menginisialisasi kamera: $e');
    }
  }

  CameraController? get controller => _controller;

  bool get isInitialized => _controller?.value.isInitialized ?? false;

  Future<String?> takePicture() async {
    if (_controller == null || !_controller!.value.isInitialized) {
      return null;
    }

    try {
      final image = await _controller!.takePicture();

      // Optimize image
      final optimizedPath = await _optimizeImage(image.path);

      return optimizedPath;
    } catch (e) {
      return null;
    }
  }

  Future<String> _optimizeImage(String imagePath) async {
    final bytes = await File(imagePath).readAsBytes();
    final image = img.decodeImage(bytes);

    if (image == null) return imagePath;

    // Resize if too large
    img.Image resized = image;
    if (image.width > 1024 || image.height > 1024) {
      resized = img.copyResize(
        image,
        width: image.width > image.height ? 1024 : null,
        height: image.height > image.width ? 1024 : null,
      );
    }

    // Compress
    final compressed = img.encodeJpg(resized, quality: 85);

    // Save optimized image
    final tempDir = await getTemporaryDirectory();
    final fileName = 'optimized_${DateTime.now().millisecondsSinceEpoch}.jpg';
    final optimizedFile = File('${tempDir.path}/$fileName');
    await optimizedFile.writeAsBytes(compressed);

    return optimizedFile.path;
  }

  void dispose() {
    _controller?.dispose();
  }
}
