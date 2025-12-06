<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AttendanceLog;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    /**
     * Get Attendance List (Filterable)
     */
    public function index(Request $request)
    {
        $query = Attendance::with(['user', 'schedule']);

        if ($request->has('user_id')) $query->where('user_id', $request->user_id);
        if ($request->has('schedule_id')) $query->where('schedule_id', $request->schedule_id);
        if ($request->has('status')) $query->where('status', $request->status);

        if ($request->has('date'))
            $query->whereDate('date', $request->date);

        if ($request->has(['start_date', 'end_date']))
            $query->dateRange($request->start_date, $request->end_date);

        return response()->json([
            'success' => true,
            'data' => $query->latest('date')->latest('check_in_time')->paginate($request->get('per_page', 10))
        ]);
    }

    /**
     * Face Recognition Check-In (Mobile)
     */
    public function checkIn(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'recognized_user_id' => 'required|exists:users,id',
            'schedule_id' => 'nullable|exists:schedules,id',
            'photo' => 'required|image|max:5120',
            'confidence' => 'required|numeric|min:0|max:1',
            'distance' => 'required|numeric|min:0|max:1',
            'location' => 'nullable|string',
            'device_info' => 'nullable|string',
        ]);

        if ($validator->fails())
            return response()->json(['success' => false, 'message' => 'Validation error', 'errors' => $validator->errors()], 422);

        // Threshold Validation
        $threshold = config('services.face.confidence_threshold', 0.75);
        $distanceThreshold = config('services.face.distance_threshold', 0.55);

        if ($request->confidence < $threshold || $request->distance > $distanceThreshold) {
            return response()->json([
                'success' => false,
                'message' => 'Wajah tidak terverifikasi. Silakan coba lagi.',
            ], 400);
        }

        $userId = $request->recognized_user_id;
        $today = now()->toDateString();

        $attendance = Attendance::firstOrNew(['user_id' => $userId, 'date' => $today]);

        if ($attendance->check_in_time) {
            return response()->json([
                'success' => false,
                'message' => 'User sudah melakukan check-in hari ini'
            ], 400);
        }

        // Save Photo
        $photoPath = $request->file('photo')->store('attendance/checkin', 'public');

        // Update Record
        $attendance->check_in_time = now()->format('H:i:s');
        $attendance->check_in_photo = $photoPath;
        $attendance->check_in_confidence = $request->confidence;
        $attendance->check_in_device = 'Mobile';
        $attendance->check_in_recognized_user_id = $userId;
        $attendance->check_in_face_verified = true;
        $attendance->schedule_id = $request->schedule_id ?? null;
        $attendance->save();

        AttendanceLog::createLog($attendance->id, $userId, 'check_in', 'Face recognition check-in');

        return response()->json(['success' => true, 'message' => 'Check-in berhasil', 'data' => $attendance], 201);
    }

    /**
     * Face Recognition Check-Out (Mobile)
     */
    public function checkOut(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'recognized_user_id' => 'required|exists:users,id',
            'photo' => 'required|image|max:5120',
            'confidence' => 'required|numeric|min:0|max:1',
            'distance' => 'required|numeric|min:0|max:1',
            'location' => 'nullable|string',
            'device_info' => 'nullable|string',
        ]);

        if ($validator->fails())
            return response()->json(['success' => false, 'message' => 'Validation error', 'errors' => $validator->errors()], 422);

        $threshold = config('services.face.confidence_threshold', 0.75);
        $distanceThreshold = config('services.face.distance_threshold', 0.55);

        if ($request->confidence < $threshold || $request->distance > $distanceThreshold) {
            return response()->json([
                'success' => false,
                'message' => 'Wajah tidak terverifikasi.'
            ], 400);
        }

        $userId = $request->recognized_user_id;
        $today = now()->toDateString();
        $attendance = Attendance::where('user_id', $userId)->whereDate('date', $today)->first();

        if (!$attendance || !$attendance->check_in_time)
            return response()->json(['success' => false, 'message' => 'User belum check-in hari ini'], 400);

        if ($attendance->check_out_time)
            return response()->json(['success' => false, 'message' => 'User sudah check-out hari ini'], 400);

        // Save Photo
        $photoPath = $request->file('photo')->store('attendance/checkout', 'public');

        // Update
        $attendance->check_out_time = now()->format('H:i:s');
        $attendance->check_out_photo = $photoPath;
        $attendance->check_out_confidence = $request->confidence;
        $attendance->check_out_device = 'Mobile';
        $attendance->check_out_recognized_user_id = $userId;
        $attendance->check_out_face_verified = true;

        // Work Duration
        if ($attendance->check_in_time) {
            $checkIn = Carbon::parse($attendance->date . ' ' . $attendance->check_in_time);
            $checkOut = Carbon::parse($attendance->date . ' ' . $attendance->check_out_time);
            $attendance->work_duration = $checkIn->diffInMinutes($checkOut);
        }

        $attendance->save();
        AttendanceLog::createLog($attendance->id, $userId, 'check_out', 'Face recognition check-out');

        return response()->json(['success' => true, 'message' => 'Check-out berhasil', 'data' => $attendance]);
    }
}
