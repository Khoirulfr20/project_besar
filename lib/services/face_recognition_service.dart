import 'dart:io'; // ✅ TAMBAHAN: Untuk validasi file
import 'package:flutter/material.dart';
import 'package:camera/camera.dart';
import 'package:provider/provider.dart';
import 'package:permission_handler/permission_handler.dart';

import '../../services/camera_service.dart';
import '../../services/location_service.dart';
import '../../providers/attendance_provider.dart';
import '../../providers/schedule_provider.dart';

class FaceCaptureScreen extends StatefulWidget {
  final bool isCheckOut;
  const FaceCaptureScreen({super.key, this.isCheckOut = false});

  @override
  State<FaceCaptureScreen> createState() => _FaceCaptureScreenState();
}

class _FaceCaptureScreenState extends State<FaceCaptureScreen> {
  final CameraService _cameraService = CameraService();
  final LocationService _locationService = LocationService();

  bool _isInit = false;
  bool _isProcessing = false;
  String _status = 'Posisikan wajah Anda di dalam lingkaran';
  String? _error;
  String? _capturedImagePath;

  // GPS Data
  double? _latitude;
  double? _longitude;
  int? _distance;

  @override
  void initState() {
    super.initState();
    _initCamera().then((_) => _loadSchedules());
  }

  Future<void> _loadSchedules() async {
    if (widget.isCheckOut) return;

    final sp = Provider.of<ScheduleProvider>(context, listen: false);
    await sp.loadTodayActiveSchedules();
    if (mounted) setState(() {});
  }

  Future<void> _initCamera() async {
    try {
      if (!await Permission.camera.isGranted) {
        if (!await Permission.camera.request().isGranted) {
          setState(
              () => _error = 'Izin kamera ditolak. Aktifkan di pengaturan.');
          return;
        }
      }
      await _cameraService.initialize();
      if (mounted) setState(() => _isInit = true);
    } catch (e) {
      setState(() => _error = 'Tidak dapat mengakses kamera: $e');
    }
  }

  @override
  void dispose() {
    try {
      _cameraService.dispose();
    } catch (_) {}
    super.dispose();
  }

  /// ✅ AMBIL GPS KOORDINAT
  Future<Map<String, dynamic>?> _getGpsLocation() async {
    try {
      debugPrint('📍 Getting GPS location...');

      // Check & request location permission
      final hasPermission =
          await _locationService.checkAndRequestLocationSettings(context);
      if (!hasPermission) {
        throw Exception('Izin lokasi atau GPS tidak aktif');
      }

      // Validate location
      final validation = await _locationService.validateLocation();

      debugPrint('✅ GPS Location obtained:');
      debugPrint('   Latitude: ${validation['latitude']}');
      debugPrint('   Longitude: ${validation['longitude']}');
      debugPrint('   Distance: ${validation['distance']}m');
      debugPrint('   Valid: ${validation['valid']}');

      // Cek apakah dalam radius
      if (!validation['valid']) {
        final distance =
            _locationService.formatDistance(validation['distance'].toDouble());
        final maxRadius = _locationService
            .formatDistance(validation['max_radius'].toDouble());

        throw Exception('Anda berada di luar area kantor!\n\n'
            'Jarak Anda: $distance\n'
            'Radius maksimal: $maxRadius\n\n'
            'Absensi hanya dapat dilakukan di area kantor.');
      }

      return validation;
    } catch (e) {
      debugPrint('❌ GPS Error: $e');
      rethrow;
    }
  }

  /// ✅ IMPROVED: Capture dengan validasi image
  Future<void> _capture() async {
    if (_isProcessing) return;

    setState(() {
      _isProcessing = true;
      _status = 'Mengambil foto...';
    });

    try {
      // ✅ STEP 1: Ambil Foto
      final imagePath = await _cameraService.takePicture();
      if (imagePath == null) throw Exception('Gagal mengambil gambar');

      _capturedImagePath = imagePath;

      setState(() {
        _status = 'Foto berhasil! Memeriksa kualitas...';
      });

      // ✅ STEP 1.5: VALIDASI IMAGE (Basic check)
      final file = File(_capturedImagePath!);
      if (!await file.exists()) {
        throw Exception('File gambar tidak ditemukan');
      }

      final fileSize = await file.length();
      if (fileSize < 1024) {
        // Less than 1KB
        throw Exception('File gambar terlalu kecil atau rusak');
      }

      debugPrint(
          '✅ Image validated: $fileSize bytes (${(fileSize / 1024).toStringAsFixed(2)} KB)');

      setState(() {
        _status = 'Gambar valid! Mengambil lokasi GPS...';
      });

      // ✅ STEP 2: Ambil & Validasi GPS
      final gpsData = await _getGpsLocation();

      if (gpsData != null) {
        _latitude = gpsData['latitude'];
        _longitude = gpsData['longitude'];
        _distance = gpsData['distance'];

        debugPrint('✅ GPS Data saved:');
        debugPrint('   Latitude: $_latitude');
        debugPrint('   Longitude: $_longitude');
        debugPrint('   Distance: $_distance m');
      }

      if (mounted) {
        setState(() {
          _status = 'Lokasi terverifikasi! Memproses...';
          _isProcessing = false;
        });

        // ✅ STEP 3: Lanjut ke dialog jadwal atau submit
        if (widget.isCheckOut) {
          await _submitAttendance(null);
        } else {
          _showScheduleDialog();
        }
      }
    } catch (e) {
      debugPrint('❌ Capture Error: $e');

      setState(() {
        _status = 'Posisikan wajah Anda di dalam lingkaran';
        _isProcessing = false;
        _capturedImagePath = null;
        _latitude = null;
        _longitude = null;
        _distance = null;
      });

      _show(false, e.toString().replaceFirst('Exception: ', ''));
    }
  }

  void _showScheduleDialog() {
    final sp = Provider.of<ScheduleProvider>(context, listen: false);
    final schedules = sp.todayActiveSchedules;

    if (schedules.isEmpty) {
      _show(false, 'Tidak ada jadwal aktif hari ini.');
      return;
    }

    int? selectedScheduleId;

    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (BuildContext dialogContext) {
        return StatefulBuilder(
          builder: (context, setDialogState) {
            return AlertDialog(
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(16),
              ),
              title: Row(
                children: [
                  Container(
                    padding: const EdgeInsets.all(8),
                    decoration: BoxDecoration(
                      color: Colors.green.shade50,
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Icon(Icons.check_circle,
                        color: Colors.green.shade700, size: 28),
                  ),
                  const SizedBox(width: 12),
                  const Expanded(
                    child: Text(
                      'Pilih Jadwal',
                      style:
                          TextStyle(fontSize: 20, fontWeight: FontWeight.bold),
                    ),
                  ),
                ],
              ),
              content: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    'Silakan pilih jadwal untuk absensi:',
                    style: TextStyle(fontSize: 14, color: Colors.black87),
                  ),
                  const SizedBox(height: 20),

                  // Info GPS
                  if (_distance != null)
                    Container(
                      padding: const EdgeInsets.all(12),
                      margin: const EdgeInsets.only(bottom: 16),
                      decoration: BoxDecoration(
                        color: Colors.green.shade50,
                        borderRadius: BorderRadius.circular(8),
                        border: Border.all(color: Colors.green.shade200),
                      ),
                      child: Row(
                        children: [
                          Icon(Icons.location_on,
                              color: Colors.green.shade700, size: 20),
                          const SizedBox(width: 8),
                          Expanded(
                            child: Text(
                              'Lokasi terverifikasi ($_distance m dari kantor)',
                              style: TextStyle(
                                fontSize: 12,
                                color: Colors.green.shade700,
                                fontWeight: FontWeight.w500,
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),

                  Container(
                    decoration: BoxDecoration(
                      border: Border.all(color: Colors.grey.shade300),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: DropdownButtonFormField<int>(
                      initialValue: selectedScheduleId,
                      decoration: InputDecoration(
                        labelText: 'Pilih Jadwal *',
                        labelStyle: TextStyle(color: Colors.grey.shade700),
                        contentPadding: const EdgeInsets.symmetric(
                            horizontal: 16, vertical: 12),
                        border: InputBorder.none,
                        prefixIcon: const Icon(Icons.schedule),
                      ),
                      isExpanded: true,
                      items: schedules.map((s) {
                        return DropdownMenuItem(
                          value: s.id,
                          child: Text(
                            s.title,
                            style: const TextStyle(fontSize: 14),
                            overflow: TextOverflow.ellipsis,
                          ),
                        );
                      }).toList(),
                      onChanged: (v) {
                        setDialogState(() {
                          selectedScheduleId = v;
                        });
                      },
                    ),
                  ),
                  if (selectedScheduleId == null)
                    Padding(
                      padding: const EdgeInsets.only(top: 8, left: 4),
                      child: Text(
                        'Pilih jadwal terlebih dahulu',
                        style: TextStyle(
                          color: Colors.orange.shade700,
                          fontSize: 12,
                        ),
                      ),
                    ),
                ],
              ),
              actions: [
                TextButton(
                  onPressed: () {
                    Navigator.of(dialogContext).pop();
                    setState(() {
                      _capturedImagePath = null;
                      _latitude = null;
                      _longitude = null;
                      _distance = null;
                      _status = 'Posisikan wajah Anda di dalam lingkaran';
                    });
                  },
                  child: const Text(
                    'Batal',
                    style: TextStyle(color: Colors.grey),
                  ),
                ),
                ElevatedButton.icon(
                  onPressed: selectedScheduleId == null
                      ? null
                      : () {
                          Navigator.of(dialogContext).pop();
                          _submitAttendance(selectedScheduleId!);
                        },
                  icon: const Icon(Icons.save),
                  label: const Text('Simpan'),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: Colors.blue,
                    foregroundColor: Colors.white,
                    disabledBackgroundColor: Colors.grey.shade300,
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(8),
                    ),
                    padding: const EdgeInsets.symmetric(
                        horizontal: 20, vertical: 12),
                  ),
                ),
              ],
            );
          },
        );
      },
    );
  }

  Future<void> _submitAttendance(int? scheduleId) async {
    if (_capturedImagePath == null) return;

    setState(() {
      _isProcessing = true;
      _status = widget.isCheckOut
          ? 'Memproses check-out...'
          : 'Memproses check-in...';
    });

    try {
      debugPrint('═══════════════════════════════════════');
      debugPrint('📸 SUBMIT ATTENDANCE START');
      debugPrint('═══════════════════════════════════════');
      debugPrint('📁 Photo Path: $_capturedImagePath');
      debugPrint('📅 Schedule ID: $scheduleId');
      debugPrint('📍 Latitude: $_latitude');
      debugPrint('📍 Longitude: $_longitude');
      debugPrint('📏 Distance: $_distance m');
      debugPrint('🔄 Is Check-Out: ${widget.isCheckOut}');
      debugPrint('═══════════════════════════════════════');

      final provider = Provider.of<AttendanceProvider>(context, listen: false);

      // ✅ KIRIM FILE PATH + GPS
      Map<String, dynamic> result;
      if (widget.isCheckOut) {
        result = await provider.checkOut(
          photoPath: _capturedImagePath!,
          latitude: _latitude,
          longitude: _longitude,
        );
      } else {
        result = await provider.checkIn(
          photoPath: _capturedImagePath!,
          scheduleId: scheduleId,
          latitude: _latitude,
          longitude: _longitude,
        );
      }

      debugPrint('═══════════════════════════════════════');
      debugPrint('📦 RESULT FROM BACKEND:');
      debugPrint('   Success: ${result['success']}');
      debugPrint('   Message: ${result['message']}');

      // ✅ Log LBPH info jika ada
      if (result['data'] != null) {
        debugPrint('   LBPH Data:');
        debugPrint('      User ID: ${result['data']['user_id']}');
        debugPrint('      Distance: ${result['data']['distance']}');
        debugPrint('      Confidence: ${result['data']['confidence']}%');
        debugPrint('      Method: ${result['data']['method']}');
      }
      debugPrint('═══════════════════════════════════════');

      if (result['success'] == true) {
        _show(true, result['message'] ?? 'Absensi berhasil!');
        await Future.delayed(const Duration(milliseconds: 900));
        if (mounted) Navigator.pop(context, true);
      } else {
        // Handle error dengan info GPS/LBPH
        String errorMessage = result['message'] ?? 'Absensi gagal';

        // Jika ada data tambahan dari backend
        if (result['data'] != null) {
          final data = result['data'];

          // Info jarak GPS (jika ada)
          if (data['distance'] != null && data['max_radius'] != null) {
            errorMessage +=
                '\n\nJarak: ${data['distance']}\nRadius maksimal: ${data['max_radius']}';
          }
        }

        throw Exception(errorMessage);
      }
    } catch (e) {
      debugPrint('═══════════════════════════════════════');
      debugPrint('❌ ATTENDANCE ERROR:');
      debugPrint('   ${e.toString()}');
      debugPrint('═══════════════════════════════════════');

      _show(false, e.toString().replaceFirst('Exception: ', ''));
      setState(() {
        _capturedImagePath = null;
        _latitude = null;
        _longitude = null;
        _distance = null;
        _status = 'Gagal menyimpan absensi. Silakan coba lagi.';
      });
    } finally {
      if (mounted) {
        setState(() => _isProcessing = false);
      }
    }
  }

  void _show(bool success, String msg) {
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(msg),
        backgroundColor: success ? Colors.green : Colors.red,
        behavior: SnackBarBehavior.floating,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
        duration: Duration(seconds: success ? 2 : 4),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Scaffold(
      backgroundColor: Colors.black,
      appBar: AppBar(
        title: Text(
          widget.isCheckOut ? 'Check-Out' : 'Check-In',
          style: const TextStyle(color: Colors.white),
        ),
        backgroundColor: Colors.black,
        iconTheme: const IconThemeData(color: Colors.white),
        elevation: 0,
      ),
      body: _error != null
          ? _errorView()
          : !_isInit
              ? const Center(
                  child: CircularProgressIndicator(color: Colors.white))
              : _buildCameraView(theme),
    );
  }

  Widget _errorView() => Center(
        child: Padding(
          padding: const EdgeInsets.all(24.0),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Container(
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: Colors.red.shade50,
                  shape: BoxShape.circle,
                ),
                child: Icon(Icons.error_outline,
                    size: 64, color: Colors.red.shade700),
              ),
              const SizedBox(height: 24),
              Text(
                _error!,
                textAlign: TextAlign.center,
                style: const TextStyle(fontSize: 16, color: Colors.white),
              ),
              const SizedBox(height: 32),
              ElevatedButton.icon(
                onPressed: () => openAppSettings(),
                icon: const Icon(Icons.settings),
                label: const Text('Buka Pengaturan'),
                style: ElevatedButton.styleFrom(
                  padding:
                      const EdgeInsets.symmetric(horizontal: 24, vertical: 14),
                ),
              )
            ],
          ),
        ),
      );

  Widget _buildCameraView(ThemeData theme) {
    final controller = _cameraService.controller!;
    return Stack(
      children: [
        Positioned.fill(child: CameraPreview(controller)),
        Positioned.fill(child: CustomPaint(painter: FaceGuidePainter())),
        Positioned(top: 0, left: 0, right: 0, child: _statusBar(theme)),
        Positioned(bottom: 40, left: 0, right: 0, child: _captureButton(theme)),
        Positioned(bottom: 150, left: 16, right: 16, child: _tips(theme)),
      ],
    );
  }

  Widget _statusBar(ThemeData theme) => Container(
        padding: const EdgeInsets.symmetric(vertical: 16, horizontal: 20),
        decoration: BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topCenter,
            end: Alignment.bottomCenter,
            colors: [
              Colors.black.withValues(alpha: 0.7),
              Colors.black.withValues(alpha: 0.3),
              Colors.transparent,
            ],
          ),
        ),
        child: Column(
          children: [
            if (_isProcessing)
              Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: theme.primaryColor.withValues(alpha: 0.9),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: const Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    SizedBox(
                      width: 16,
                      height: 16,
                      child: CircularProgressIndicator(
                        color: Colors.white,
                        strokeWidth: 2,
                      ),
                    ),
                    SizedBox(width: 12),
                    Text(
                      'Memproses...',
                      style: TextStyle(
                        color: Colors.white,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                  ],
                ),
              )
            else
              Text(
                _status,
                textAlign: TextAlign.center,
                style: const TextStyle(
                  color: Colors.white,
                  fontSize: 16,
                  fontWeight: FontWeight.w500,
                  shadows: [
                    Shadow(
                      color: Colors.black45,
                      blurRadius: 4,
                    ),
                  ],
                ),
              ),
          ],
        ),
      );

  Widget _captureButton(ThemeData theme) => Center(
        child: GestureDetector(
          onTap: _isProcessing ? null : _capture,
          child: Container(
            width: 80,
            height: 80,
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              color: _isProcessing ? Colors.grey.shade400 : Colors.white,
              border: Border.all(
                color: _isProcessing ? Colors.grey : theme.primaryColor,
                width: 4,
              ),
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withValues(alpha: 0.3),
                  blurRadius: 10,
                  spreadRadius: 2,
                )
              ],
            ),
            child: _isProcessing
                ? Padding(
                    padding: const EdgeInsets.all(16),
                    child: CircularProgressIndicator(
                      strokeWidth: 3,
                      valueColor: AlwaysStoppedAnimation(theme.primaryColor),
                    ),
                  )
                : Icon(Icons.camera_alt, size: 35, color: theme.primaryColor),
          ),
        ),
      );

  Widget _tips(ThemeData theme) => Container(
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: Colors.black.withValues(alpha: 0.7),
          borderRadius: BorderRadius.circular(12),
          border: Border.all(
            color: Colors.white.withValues(alpha: 0.2),
          ),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Icon(
                  Icons.info_outline,
                  color: theme.primaryColor,
                  size: 20,
                ),
                const SizedBox(width: 8),
                const Text(
                  'Tips:',
                  style: TextStyle(
                    color: Colors.white,
                    fontWeight: FontWeight.bold,
                    fontSize: 14,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 8),
            const Text(
              '• Pastikan pencahayaan cukup\n'
              '• Lihat langsung ke kamera\n'
              '• Jangan gunakan masker/kacamata hitam\n'
              '• Pastikan GPS aktif',
              style: TextStyle(
                color: Colors.white70,
                fontSize: 12,
                height: 1.5,
              ),
            ),
          ],
        ),
      );
}

class FaceGuidePainter extends CustomPainter {
  @override
  void paint(Canvas canvas, Size s) {
    final paint = Paint()
      ..color = Colors.white.withValues(alpha: 0.9)
      ..style = PaintingStyle.stroke
      ..strokeWidth = 3;
    final center = Offset(s.width / 2, s.height / 2 - 40);
    final radius = s.width * 0.34;
    canvas.drawOval(
      Rect.fromCenter(center: center, width: radius * 2, height: radius * 2.2),
      paint,
    );
  }

  @override
  bool shouldRepaint(_) => false;
}
