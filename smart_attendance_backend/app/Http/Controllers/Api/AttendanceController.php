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
    public function index(Request $request)
    {
        $query = Attendance::with(['user', 'schedule']);

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('schedule_id')) {
            $query->where('schedule_id', $request->schedule_id);
        }

        if ($request->has('date')) {
            $query->whereDate('date', $request->date);
        }

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->dateRange($request->start_date, $request->end_date);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $perPage = $request->get('per_page', 10);
        $attendances = $query->latest('date')->latest('check_in_time')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $attendances
        ]);
    }

    public function checkIn(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'schedule_id' => 'nullable|exists:schedules,id',
            'photo' => 'required|image|max:5120',
            'confidence' => 'required|numeric|min:0|max:1',
            'location' => 'nullable|string',
            'device_info' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = auth()->user();
        $today = now()->toDateString();

        // Check if already checked in today
        $existingAttendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', $today)
            ->first();

        if ($existingAttendance && $existingAttendance->check_in_time) {
            return response()->json([
                'success' => false,
                'message' => 'Anda sudah melakukan check-in hari ini'
            ], 400);
        }

        // Validate confidence threshold
        $threshold = Setting::getValue('face_confidence_threshold', 0.75);
        if ($request->confidence < $threshold) {
            return response()->json([
                'success' => false,
                'message' => 'Confidence score terlalu rendah. Silakan coba lagi dengan pencahayaan yang lebih baik.'
            ], 400);
        }

        // Store photo
        $photoPath = $request->file('photo')->store('attendance/checkin', 'public');

        // Create or update attendance
        if ($existingAttendance) {
            $existingAttendance->checkIn(
                $photoPath,
                $request->confidence,
                $request->location,
                $request->device_info
            );
            $attendance = $existingAttendance;
        } else {
            $attendance = Attendance::create([
                'user_id' => $user->id,
                'schedule_id' => $request->schedule_id,
                'date' => $today,
            ]);

            $attendance->checkIn(
                $photoPath,
                $request->confidence,
                $request->location,
                $request->device_info
            );
        }

        // Create log
        AttendanceLog::createLog(
            $attendance->id,
            $user->id,
            'check_in',
            'User melakukan check-in'
        );

        return response()->json([
            'success' => true,
            'message' => 'Check-in berhasil',
            'data' => $attendance->load('schedule')
        ], 201);
    }

    public function checkOut(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'photo' => 'required|image|max:5120',
            'confidence' => 'required|numeric|min:0|max:1',
            'location' => 'nullable|string',
            'device_info' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = auth()->user();
        $today = now()->toDateString();

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', $today)
            ->first();

        if (!$attendance || !$attendance->check_in_time) {
            return response()->json([
                'success' => false,
                'message' => 'Anda belum melakukan check-in'
            ], 400);
        }

        if ($attendance->check_out_time) {
            return response()->json([
                'success' => false,
                'message' => 'Anda sudah melakukan check-out hari ini'
            ], 400);
        }

        // Validate confidence threshold
        $threshold = Setting::getValue('face_confidence_threshold', 0.75);
        if ($request->confidence < $threshold) {
            return response()->json([
                'success' => false,
                'message' => 'Confidence score terlalu rendah. Silakan coba lagi.'
            ], 400);
        }

        // Store photo
        $photoPath = $request->file('photo')->store('attendance/checkout', 'public');

        $attendance->checkOut(
            $photoPath,
            $request->confidence,
            $request->location,
            $request->device_info
        );

        // Create log
        AttendanceLog::createLog(
            $attendance->id,
            $user->id,
            'check_out',
            'User melakukan check-out'
        );

        return response()->json([
            'success' => true,
            'message' => 'Check-out berhasil',
            'data' => $attendance->load('schedule')
        ]);
    }

    public function myAttendance(Request $request)
    {
        $user = auth()->user();
        $query = Attendance::where('user_id', $user->id)->with('schedule');

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->dateRange($request->start_date, $request->end_date);
        } else {
            // Default: last 30 days
            $query->dateRange(now()->subDays(30)->toDateString(), now()->toDateString());
        }

        $attendances = $query->latest('date')->get();

        return response()->json([
            'success' => true,
            'data' => $attendances
        ]);
    }

    public function todayAttendance()
    {
        $user = auth()->user();
        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', now()->toDateString())
            ->with('schedule')
            ->first();

        return response()->json([
            'success' => true,
            'data' => $attendance
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:present,late,absent,excused,leave',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $attendance = Attendance::find($id);

        if (!$attendance) {
            return response()->json([
                'success' => false,
                'message' => 'Attendance tidak ditemukan'
            ], 404);
        }

        $oldStatus = $attendance->status;
        $attendance->status = $request->status;
        
        if ($request->has('notes')) {
            $attendance->notes = $request->notes;
        }

        $attendance->save();

        // Create log
        AttendanceLog::createLog(
            $attendance->id,
            auth()->id(),
            'status_changed',
            'Status diubah dari ' . $oldStatus . ' ke ' . $request->status,
            ['status' => $oldStatus],
            ['status' => $request->status]
        );

        return response()->json([
            'success' => true,
            'message' => 'Status berhasil diupdate',
            'data' => $attendance
        ]);
    }

    public function approve(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $attendance = Attendance::find($id);

        if (!$attendance) {
            return response()->json([
                'success' => false,
                'message' => 'Attendance tidak ditemukan'
            ], 404);
        }

        $attendance->approve(auth()->id(), $request->notes);

        // Create log
        AttendanceLog::createLog(
            $attendance->id,
            auth()->id(),
            'approved',
            'Kehadiran disetujui'
        );

        return response()->json([
            'success' => true,
            'message' => 'Kehadiran berhasil disetujui',
            'data' => $attendance
        ]);
    }

    public function history(Request $request, $id)
    {
        $attendance = Attendance::find($id);

        if (!$attendance) {
            return response()->json([
                'success' => false,
                'message' => 'Attendance tidak ditemukan'
            ], 404);
        }

        $logs = $attendance->logs()->with('user')->latest()->get();

        return response()->json([
            'success' => true,
            'data' => $logs
        ]);
    }

    public function statistics(Request $request)
    {
        $userId = $request->get('user_id', auth()->id());
        $startDate = $request->get('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->get('end_date', now()->toDateString());

        $attendances = Attendance::where('user_id', $userId)
            ->dateRange($startDate, $endDate)
            ->get();

        $stats = [
            'total_days' => $attendances->count(),
            'present' => $attendances->where('status', 'present')->count(),
            'late' => $attendances->where('status', 'late')->count(),
            'absent' => $attendances->where('status', 'absent')->count(),
            'excused' => $attendances->where('status', 'excused')->count(),
            'leave' => $attendances->where('status', 'leave')->count(),
            'attendance_rate' => 0,
            'average_work_duration' => 0,
        ];

        if ($stats['total_days'] > 0) {
            $presentDays = $stats['present'] + $stats['late'];
            $stats['attendance_rate'] = round(($presentDays / $stats['total_days']) * 100, 2);
        }

        $workDurations = $attendances->whereNotNull('work_duration')->pluck('work_duration');
        if ($workDurations->count() > 0) {
            $stats['average_work_duration'] = round($workDurations->avg(), 0);
        }

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}