import 'dart:io';
import 'dart:convert';
import 'package:camera/camera.dart';
import 'package:path_provider/path_provider.dart';
import 'package:image/image.dart' as img;
import 'package:permission_handler/permission_handler.dart';
import '../utils/app_logger.dart';

class CameraService {
  CameraController? _controller;
  List<CameraDescription>? _cameras;

  /// Initialize camera safely
  Future<void> initialize() async {
    AppLogger.debug('CameraService: Starting initialization...');

    // 📌 1) Permission
    if (!(await Permission.camera.isGranted)) {
      AppLogger.info('Requesting camera permission...');
      var result = await Permission.camera.request();
      if (!result.isGranted) {
        AppLogger.error('Camera permission denied');
        throw Exception('Akses kamera ditolak. Aktifkan di pengaturan.');
      }
    }
    AppLogger.success('Camera permission granted');

    // 📌 2) Ambil daftar kamera
    try {
      _cameras = await availableCameras();
    } catch (e) {
      AppLogger.error('Failed to get available cameras', e);
      throw Exception('Gagal mendapatkan daftar kamera: $e');
    }

    if (_cameras == null || _cameras!.isEmpty) {
      AppLogger.error('No cameras available');
      throw Exception('Tidak ada kamera yang tersedia.');
    }
    AppLogger.info('Available cameras: ${_cameras!.length}');

    // 📌 3) Ambil kamera depan
    CameraDescription selectedCamera = _cameras!.firstWhere(
      (cam) => cam.lensDirection == CameraLensDirection.front,
      orElse: () => _cameras!.first,
    );
    AppLogger.info('Selected camera: ${selectedCamera.lensDirection}');

    // 📌 4) Pilih resolusi
    ResolutionPreset preset =
        Platform.isAndroid ? ResolutionPreset.medium : ResolutionPreset.high;

    AppLogger.debug('Creating CameraController...');
    _controller = CameraController(
      selectedCamera,
      preset,
      enableAudio: false,
      imageFormatGroup: ImageFormatGroup.jpeg,
    );

    // 📌 5) Initialize
    try {
      AppLogger.debug('Initializing camera controller...');
      await _controller!.initialize();

      // ✅ TAMBAHKAN CEK SETELAH INITIALIZE
      if (!_controller!.value.isInitialized) {
        throw Exception(
            'Controller initialized but value.isInitialized is false');
      }

      AppLogger.success('Camera initialized successfully');
      AppLogger.debug('Preview size: ${_controller!.value.previewSize}');
    } catch (e, stackTrace) {
      AppLogger.error('Camera initialization failed', e, stackTrace);
      _controller?.dispose();
      _controller = null;
      throw Exception('Gagal menginisialisasi kamera: $e');
    }
  }

  CameraController? get controller => _controller;
  bool get isInitialized => _controller?.value.isInitialized ?? false;

  Future<String?> takePicture() async {
    if (!isInitialized) {
      AppLogger.warning('Camera not initialized');
      return null;
    }

    try {
      AppLogger.debug('Taking picture...');
      final file = await _controller!.takePicture();
      AppLogger.success('Picture taken: ${file.path}');
      return await _optimizeImage(file.path);
    } catch (e) {
      AppLogger.error('Failed to take picture', e);
      return null;
    }
  }

  Future<String> _optimizeImage(String imagePath) async {
    try {
      AppLogger.debug('Optimizing image...');
      final bytes = await File(imagePath).readAsBytes();
      final image = img.decodeImage(bytes);

      if (image == null) {
        AppLogger.warning('Failed to decode image, returning original');
        return imagePath;
      }

      // 🔧 Resize jika terlalu besar
      img.Image resized = image;
      if (image.width > 1024 || image.height > 1024) {
        resized = img.copyResize(image, width: 1024);
        AppLogger.debug('Image resized to 1024px');
      }

      // ⬇️ Compress
      final compressed = img.encodeJpg(resized, quality: 85);
      final tempDir = await getTemporaryDirectory();
      final newPath =
          '${tempDir.path}/optimized_${DateTime.now().millisecondsSinceEpoch}.jpg';
      await File(newPath).writeAsBytes(compressed);

      AppLogger.success('Image optimized: ${compressed.length} bytes');
      return newPath;
    } catch (e) {
      AppLogger.warning('Image optimization failed, returning original: $e');
      return imagePath;
    }
  }

  /// ✅ CONVERT IMAGE TO BASE64
  Future<String> toBase64(String imagePath) async {
    try {
      final bytes = await File(imagePath).readAsBytes();
      img.Image? image = img.decodeImage(bytes);

      if (image == null) throw Exception("Gagal decode image");

      // 🔄 ROTATE BASED ON CAMERA SENSOR
      if (_controller?.description.sensorOrientation != null) {
        final rotation = _controller!.description.sensorOrientation;
        image = img.copyRotate(image, angle: rotation);
      }

      // 🔁 FIX MIRROR FRONT CAMERA
      if (_controller?.description.lensDirection == CameraLensDirection.front) {
        image = img.flipHorizontal(image);
      }

      // 📦 FORMAT TO JPG & ENCODE BASE64
      final fixedBytes = img.encodeJpg(image, quality: 90);
      return "data:image/jpeg;base64,${base64Encode(fixedBytes)}";
    } catch (e) {
      AppLogger.error("Error converting to Base64", e);
      rethrow;
    }
  }

  void dispose() {
    if (_controller != null) {
      try {
        AppLogger.debug('Disposing camera controller...');
        _controller!.dispose();
        AppLogger.success('Camera disposed');
      } catch (e) {
        AppLogger.warning('Error disposing camera: $e');
      }
    }
  }
}
