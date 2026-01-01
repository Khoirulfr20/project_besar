<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AttendanceLog;
use App\Models\Setting;
use App\Models\User;
use App\Helpers\LocationHelper; // ✅ IMPORT HELPER
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $query = Attendance::with(['user', 'schedule']);

        if ($request->has('user_id')) $query->where('user_id', $request->user_id);
        if ($request->has('schedule_id')) $query->where('schedule_id', $request->schedule_id);
        if ($request->has('status')) $query->where('status', $request->status);

        if ($request->has('date'))
            $query->whereDate('date', $request->date);

        if ($request->has(['start_date', 'end_date']))
            $query->whereBetween('date', [$request->start_date, $request->end_date]);

        return response()->json([
            'success' => true,
            'data' => $query->latest('date')->latest('check_in_time')->paginate($request->get('per_page', 10))
        ]);
    }

    /**
     * ✅ CHECK-IN dengan GPS Validation
     */
    public function checkIn(Request $request)
    {
        // ✅ Deteksi sumber request
        $isFromMobile = $request->header('X-Source') === 'mobile-app';
        $isFromAdmin = $request->header('X-Source') === 'admin-panel';

        // ✅ Validasi berbeda untuk mobile vs admin
        if ($isFromMobile) {
            $validator = Validator::make($request->all(), [
                'photo' => 'required|image|max:5120',
                'latitude' => 'required|numeric|between:-90,90',  // WAJIB untuk mobile
                'longitude' => 'required|numeric|between:-180,180', // WAJIB untuk mobile
                'schedule_id' => 'nullable|exists:schedules,id',
                'device_info' => 'nullable|string',
            ]);
        } else {
            // Admin panel - GPS tidak wajib
            $validator = Validator::make($request->all(), [
                'photo' => 'required|image|max:5120',
                'recognized_user_id' => 'nullable|exists:users,id',
                'schedule_id' => 'nullable|exists:schedules,id',
                'device_info' => 'nullable|string',
                // latitude & longitude OPSIONAL untuk admin
            ]);
        }

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            Log::info('=== CHECK-IN START ===');
            Log::info('Source: ' . ($isFromMobile ? 'MOBILE APP' : 'ADMIN PANEL'));

            // ✅ GPS VALIDATION (HANYA UNTUK MOBILE APP)
            $gpsData = null;
            if ($isFromMobile && LocationHelper::isGpsValidationEnabled()) {
                $userLat = $request->latitude;
                $userLon = $request->longitude;

                Log::info('GPS Validation Start', [
                    'user_lat' => $userLat,
                    'user_lon' => $userLon,
                ]);

                // Validasi format koordinat
                if (!LocationHelper::isValidCoordinate($userLat, $userLon)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Koordinat GPS tidak valid.',
                    ], 400);
                }

                // Validasi jarak ke kantor
                $validation = LocationHelper::validateLocation($userLat, $userLon);

                Log::info('GPS Validation Result', [
                    'valid' => $validation['valid'],
                    'distance' => $validation['distance'],
                    'max_radius' => $validation['max_radius'],
                ]);

                if (!$validation['valid']) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Anda berada di luar radius kantor. Absensi hanya dapat dilakukan di area kantor.',
                        'data' => [
                            'distance' => LocationHelper::formatDistance($validation['distance']),
                            'max_radius' => LocationHelper::formatDistance($validation['max_radius']),
                            'office_name' => $validation['office_location']['name'],
                        ]
                    ], 400);
                }

                // Simpan data GPS untuk disimpan nanti
                $gpsData = [
                    'latitude' => $userLat,
                    'longitude' => $userLon,
                    'distance' => $validation['distance'],
                ];

                Log::info('GPS Validation PASSED! ✅', $gpsData);
            }

            // ✅ FACE RECOGNITION PROCESS
            $file = $request->file('photo');
            $binary = file_get_contents($file->getRealPath());

            Log::info('Sending photo to Python API...');

            $response = Http::timeout(15)
                ->attach('image', $binary, 'photo.jpg')
                ->post(config('services.face_api.url') . '/recognize');

            if (!$response->ok()) {
                Log::error('Python API Error', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak dapat menghubungi Face Recognition Server.',
                ], 500);
            }

            $body = $response->json();
            Log::info('Python API Response', ['body' => $body]);

            if (!($body['success'] ?? false)) {
                return response()->json([
                    'success' => false,
                    'message' => $body['message'] ?? 'Wajah tidak dikenali. Pastikan Anda sudah registrasi.',
                ], 400);
            }

            $recognizedData = $body['data'];
            $userId = $recognizedData['user_id'];
            $confidence = $recognizedData['confidence']; // Percentage 0-100
            $distance = $recognizedData['distance']; // LBPH distance (lower = better)

            Log::info('Face Recognition Result', [
                'user_id' => $userId,
                'confidence' => $confidence,
                'distance' => $distance,
                'method' => $recognizedData['method'] ?? 'LBPH'
            ]);

            // ✅ LBPH VALIDATION: Check DISTANCE only
            $distanceThreshold = config('services.face.distance_threshold', 30.0);

            Log::info('Validating face distance', [
                'distance' => $distance,
                'threshold' => $distanceThreshold,
            ]);

            if ($distance > $distanceThreshold) {
                Log::warning('Face verification FAILED - Distance too high');

                return response()->json([
                    'success' => false,
                    'message' => 'Wajah tidak terverifikasi. Jarak pengenalan terlalu tinggi. Silakan coba lagi dengan pencahayaan yang lebih baik.',
                    'debug' => [
                        'distance' => $distance,
                        'max_distance' => $distanceThreshold,
                        'confidence_percentage' => $confidence,
                    ]
                ], 400);
            }

            Log::info('Face verification PASSED! ✅');

            // ✅ USER VALIDATION
            $user = User::find($userId);
            if (!$user || !$user->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'User tidak ditemukan atau tidak aktif.',
                ], 400);
            }

            // ✅ CHECK DUPLICATE CHECK-IN
            $today = now()->toDateString();
            $attendance = Attendance::where('user_id', $userId)
                ->whereDate('date', $today)
                ->first();

            if ($attendance && $attendance->check_in_time) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda sudah melakukan check-in hari ini pada ' . $attendance->check_in_time,
                ], 400);
            }

            // ✅ SAVE PHOTO
            $photoPath = $file->store('attendance/checkin', 'public');
            Log::info('Photo saved', ['path' => $photoPath]);

            // ✅ CREATE OR UPDATE ATTENDANCE
            if (!$attendance) {
                $attendance = new Attendance();
                $attendance->user_id = $userId;
                $attendance->date = $today;
            }

            $attendance->check_in_time = now()->format('H:i:s');
            $attendance->check_in_photo = $photoPath;
            $attendance->check_in_confidence = $confidence;
            $attendance->check_in_device = $isFromMobile ? 'Mobile App' : 'Admin Panel';
            $attendance->check_in_recognized_user_id = $userId;
            $attendance->check_in_face_verified = true;
            $attendance->schedule_id = $request->schedule_id ?? null;
            $attendance->check_in_method = $isFromAdmin ? 'manual' : 'face_recognition';

            // ✅ SAVE GPS DATA (jika ada)
            if ($gpsData) {
                $attendance->check_in_latitude = $gpsData['latitude'];
                $attendance->check_in_longitude = $gpsData['longitude'];
                $attendance->check_in_distance = $gpsData['distance'];
                $attendance->admin_entry = false; // Dari mobile app
            } else {
                $attendance->admin_entry = true; // Dari admin panel
            }

            $this->determineStatus($attendance);
            $attendance->save();

            Log::info('Attendance saved', ['id' => $attendance->id]);

            // ✅ CREATE LOG
            AttendanceLog::createLog(
                $attendance->id,
                $userId,
                'check_in',
                $isFromMobile 
                    ? 'Face recognition check-in via mobile app with GPS validation'
                    : 'Face recognition check-in via admin panel'
            );

            Log::info('=== CHECK-IN SUCCESS ===');

            return response()->json([
                'success' => true,
                'message' => 'Check-in berhasil! Selamat bekerja, ' . $user->name . '.',
                'data' => $attendance->load(['user', 'schedule'])
            ], 201);

        } catch (\Exception $e) {
            Log::error('Check-in Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ✅ CHECK-OUT dengan GPS Validation
     */
    public function checkOut(Request $request)
    {
        // ✅ Deteksi sumber request
        $isFromMobile = $request->header('X-Source') === 'mobile-app';
        $isFromAdmin = $request->header('X-Source') === 'admin-panel';

        // ✅ Validasi berbeda untuk mobile vs admin
        if ($isFromMobile) {
            $validator = Validator::make($request->all(), [
                'photo' => 'required|image|max:5120',
                'latitude' => 'required|numeric|between:-90,90',  // WAJIB untuk mobile
                'longitude' => 'required|numeric|between:-180,180', // WAJIB untuk mobile
                'device_info' => 'nullable|string',
            ]);
        } else {
            $validator = Validator::make($request->all(), [
                'photo' => 'required|image|max:5120',
                'recognized_user_id' => 'nullable|exists:users,id',
                'device_info' => 'nullable|string',
            ]);
        }

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            Log::info('=== CHECK-OUT START ===');
            Log::info('Source: ' . ($isFromMobile ? 'MOBILE APP' : 'ADMIN PANEL'));

            // ✅ GPS VALIDATION (HANYA UNTUK MOBILE APP)
            $gpsData = null;
            if ($isFromMobile && LocationHelper::isGpsValidationEnabled()) {
                $userLat = $request->latitude;
                $userLon = $request->longitude;

                Log::info('GPS Validation Start', [
                    'user_lat' => $userLat,
                    'user_lon' => $userLon,
                ]);

                if (!LocationHelper::isValidCoordinate($userLat, $userLon)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Koordinat GPS tidak valid.',
                    ], 400);
                }

                $validation = LocationHelper::validateLocation($userLat, $userLon);

                Log::info('GPS Validation Result', [
                    'valid' => $validation['valid'],
                    'distance' => $validation['distance'],
                    'max_radius' => $validation['max_radius'],
                ]);

                if (!$validation['valid']) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Anda berada di luar radius kantor. Absensi hanya dapat dilakukan di area kantor.',
                        'data' => [
                            'distance' => LocationHelper::formatDistance($validation['distance']),
                            'max_radius' => LocationHelper::formatDistance($validation['max_radius']),
                            'office_name' => $validation['office_location']['name'],
                        ]
                    ], 400);
                }

                $gpsData = [
                    'latitude' => $userLat,
                    'longitude' => $userLon,
                    'distance' => $validation['distance'],
                ];

                Log::info('GPS Validation PASSED! ✅', $gpsData);
            }

            // ✅ FACE RECOGNITION PROCESS
            $file = $request->file('photo');
            $binary = file_get_contents($file->getRealPath());

            $response = Http::timeout(15)
                ->attach('image', $binary, 'photo.jpg')
                ->post(config('services.face_api.url') . '/recognize');

            if (!$response->ok()) {
                Log::error('Python API Error');
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak dapat menghubungi Face Recognition Server.',
                ], 500);
            }

            $body = $response->json();

            if (!($body['success'] ?? false)) {
                return response()->json([
                    'success' => false,
                    'message' => $body['message'] ?? 'Wajah tidak dikenali.',
                ], 400);
            }

            $recognizedData = $body['data'];
            $userId = $recognizedData['user_id'];
            $confidence = $recognizedData['confidence']; // Percentage 0-100
            $distance = $recognizedData['distance']; // LBPH distance

            Log::info('Face Recognition Result', [
                'user_id' => $userId,
                'confidence' => $confidence,
                'distance' => $distance,
            ]);

            // ✅ LBPH VALIDATION: Check DISTANCE only
            $distanceThreshold = config('services.face.distance_threshold', 30.0);

            if ($distance > $distanceThreshold) {
                Log::warning('Check-out face verification FAILED');
                
                return response()->json([
                    'success' => false,
                    'message' => 'Wajah tidak terverifikasi. Silakan coba lagi dengan pencahayaan yang lebih baik.',
                    'debug' => [
                        'distance' => $distance,
                        'max_distance' => $distanceThreshold,
                        'confidence_percentage' => $confidence,
                    ]
                ], 400);
            }

            Log::info('Check-out face verification PASSED! ✅');

            // ✅ CHECK ATTENDANCE TODAY
            $today = now()->toDateString();
            $attendance = Attendance::where('user_id', $userId)
                ->whereDate('date', $today)
                ->first();

            if (!$attendance || !$attendance->check_in_time) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda belum check-in hari ini. Silakan check-in terlebih dahulu.',
                ], 400);
            }

            if ($attendance->check_out_time) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda sudah check-out hari ini pada ' . $attendance->check_out_time,
                ], 400);
            }

            // ✅ SAVE PHOTO
            $photoPath = $file->store('attendance/checkout', 'public');

            // ✅ UPDATE ATTENDANCE
            $attendance->check_out_time = now()->format('H:i:s');
            $attendance->check_out_photo = $photoPath;
            $attendance->check_out_confidence = $confidence;
            $attendance->check_out_device = $isFromMobile ? 'Mobile App' : 'Admin Panel';
            $attendance->check_out_recognized_user_id = $userId;
            $attendance->check_out_face_verified = true;
            $attendance->check_out_method = $isFromAdmin ? 'manual' : 'face_recognition';

            // ✅ SAVE GPS DATA (jika ada)
            if ($gpsData) {
                $attendance->check_out_latitude = $gpsData['latitude'];
                $attendance->check_out_longitude = $gpsData['longitude'];
                $attendance->check_out_distance = $gpsData['distance'];
            }

            // ✅ CALCULATE WORK DURATION
            if ($attendance->check_in_time) {
                $checkIn = Carbon::parse($attendance->date . ' ' . $attendance->check_in_time);
                $checkOut = Carbon::parse($attendance->date . ' ' . $attendance->check_out_time);
                $attendance->work_duration = $checkIn->diffInMinutes($checkOut);
            }

            $attendance->save();

            // ✅ CREATE LOG
            AttendanceLog::createLog(
                $attendance->id,
                $userId,
                'check_out',
                $isFromMobile 
                    ? 'Face recognition check-out via mobile app with GPS validation'
                    : 'Face recognition check-out via admin panel'
            );

            $user = User::find($userId);
            Log::info('=== CHECK-OUT SUCCESS ===');

            return response()->json([
                'success' => true,
                'message' => 'Check-out berhasil! Terima kasih, ' . $user->name . '.',
                'data' => $attendance->load(['user', 'schedule'])
            ]);

        } catch (\Exception $e) {
            Log::error('Check-out Error', [
                'message' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ✅ FIXED: Determine status based on schedule
     */
    private function determineStatus($attendance)
    {
        if (!$attendance->schedule) {
            $attendance->status = 'present';
            return;
        }

        try {
            $scheduleStart = Carbon::parse($attendance->date . ' ' . $attendance->schedule->start_time);
            $checkInTime = Carbon::parse($attendance->date . ' ' . $attendance->check_in_time);

            $toleranceMinutes = 15;
            $settingValue = Setting::where('key', 'late_tolerance_minutes')->value('value');
            if ($settingValue !== null) {
                $toleranceMinutes = (int) $settingValue;
            }

            $lateThreshold = $scheduleStart->copy()->addMinutes($toleranceMinutes);

            if ($checkInTime->greaterThan($lateThreshold)) {
                $attendance->status = 'late';
            } else {
                $attendance->status = 'present';
            }
        } catch (\Exception $e) {
            Log::error('Error determining status', ['error' => $e->getMessage()]);
            $attendance->status = 'present';
        }
    }

    public function myAttendance(Request $request)
    {
        $user = $request->user();

        $query = Attendance::where('user_id', $user->id)->with(['schedule']);

        if ($request->has(['start_date', 'end_date'])) {
            $query->whereBetween('date', [
                $request->start_date,
                $request->end_date
            ]);
        }

        $attendances = $query->orderBy('date', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $attendances
        ]);
    }

    public function todayAttendance(Request $request)
    {
        $user = $request->user();
        $today = now()->toDateString();

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', $today)
            ->with(['schedule'])
            ->first();

        return response()->json([
            'success' => true,
            'data' => $attendance
        ]);
    }

    public function statistics(Request $request)
    {
        $user = $request->user();

        $query = Attendance::where('user_id', $user->id);

        if ($request->has(['start_date', 'end_date'])) {
            $query->whereBetween('date', [
                $request->start_date,
                $request->end_date
            ]);
        }

        $attendances = $query->get();

        $stats = [
            'total' => $attendances->count(),
            'present' => $attendances->where('status', 'present')->count(),
            'late' => $attendances->where('status', 'late')->count(),
            'absent' => $attendances->where('status', 'absent')->count(),
            'excused' => $attendances->where('status', 'excused')->count(),
            'leave' => $attendances->where('status', 'leave')->count(),
            'total_work_duration' => $attendances->sum('work_duration'),
            'average_work_duration' => round($attendances->avg('work_duration'), 2),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}