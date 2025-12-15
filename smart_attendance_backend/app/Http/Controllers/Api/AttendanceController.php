<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AttendanceLog;
use App\Models\Setting;
use App\Models\User;
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

public function checkIn(Request $request)
{
    $validator = Validator::make($request->all(), [
        'photo' => 'required|image|max:5120',
        'schedule_id' => 'nullable|exists:schedules,id',
        'device_info' => 'nullable|string',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => 'Validation error',
            'errors' => $validator->errors()
        ], 422);
    }

    try {
        Log::info('=== CHECK-IN START ===');

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
        $confidence = $recognizedData['confidence'];
        $distance = $recognizedData['distance'] ?? 0;

        Log::info('Face Recognition Result', [
            'user_id' => $userId,
            'confidence' => $confidence,
            'distance' => $distance,
        ]);

        // ✅ DISTANCE ONLY CHECK
        $distanceThreshold = config('services.face.distance_threshold', 30.0);

        Log::info('Validating distance', [
            'distance' => $distance,
            'threshold' => $distanceThreshold,
        ]);

        if ($distance > $distanceThreshold) {
            Log::warning('Face verification FAILED');

            return response()->json([
                'success' => false,
                'message' => 'Wajah tidak terverifikasi. Jarak wajah terlalu jauh. Silakan coba lagi dengan pencahayaan yang lebih baik.',
                'debug' => [
                    'distance' => $distance,
                    'max_distance' => $distanceThreshold,
                ]
            ], 400);
        }

        Log::info('Face verification PASSED! ✅');

        $user = User::find($userId);
        if (!$user || !$user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'User tidak ditemukan atau tidak aktif.',
            ], 400);
        }

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

        $photoPath = $file->store('attendance/checkin', 'public');
        Log::info('Photo saved', ['path' => $photoPath]);

        if (!$attendance) {
            $attendance = new Attendance();
            $attendance->user_id = $userId;
            $attendance->date = $today;
        }

        $attendance->check_in_time = now()->format('H:i:s');
        $attendance->check_in_photo = $photoPath;
        $attendance->check_in_confidence = $confidence;
        $attendance->check_in_device = 'Mobile App';
        $attendance->check_in_recognized_user_id = $userId;
        $attendance->check_in_face_verified = true;
        $attendance->schedule_id = $request->schedule_id ?? null;

        $this->determineStatus($attendance);
        $attendance->save();

        Log::info('Attendance saved', ['id' => $attendance->id]);

        AttendanceLog::createLog(
            $attendance->id,
            $userId,
            'check_in',
            'Face recognition check-in via mobile app'
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

public function checkOut(Request $request)
{
    $validator = Validator::make($request->all(), [
        'photo' => 'required|image|max:5120',
        'device_info' => 'nullable|string',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => 'Validation error',
            'errors' => $validator->errors()
        ], 422);
    }

    try {
        Log::info('=== CHECK-OUT START ===');

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
        $confidence = $recognizedData['confidence'];
        $distance = $recognizedData['distance'] ?? 0;

        Log::info('Face Recognition Result', [
            'user_id' => $userId,
            'distance' => $distance,
        ]);

        // ✅ DISTANCE ONLY CHECK
        $distanceThreshold = config('services.face.distance_threshold', 30.0);

        if ($distance > $distanceThreshold) {
            return response()->json([
                'success' => false,
                'message' => 'Wajah tidak terverifikasi. Silakan coba lagi.',
            ], 400);
        }

        Log::info('Face verification PASSED! ✅');

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

        $photoPath = $file->store('attendance/checkout', 'public');

        $attendance->check_out_time = now()->format('H:i:s');
        $attendance->check_out_photo = $photoPath;
        $attendance->check_out_confidence = $confidence;
        $attendance->check_out_device = 'Mobile App';
        $attendance->check_out_recognized_user_id = $userId;
        $attendance->check_out_face_verified = true;

        if ($attendance->check_in_time) {
            $checkIn = Carbon::parse($attendance->date . ' ' . $attendance->check_in_time);
            $checkOut = Carbon::parse($attendance->date . ' ' . $attendance->check_out_time);
            $attendance->work_duration = $checkIn->diffInMinutes($checkOut);
        }

        $attendance->save();

        AttendanceLog::createLog(
            $attendance->id,
            $userId,
            'check_out',
            'Face recognition check-out via mobile app'
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

        // ✅ FIX: Ensure integer type
        $toleranceMinutes = 15;
        $settingValue = Setting::where('key', 'late_tolerance_minutes')->value('value');
        if ($settingValue !== null) {
            $toleranceMinutes = (int) $settingValue;
        }

        // ✅ FIX: Use copy() to avoid mutation
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