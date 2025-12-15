<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ScheduleController extends Controller
{
    public function index(Request $request)
    {
        $query = Schedule::with(['creator', 'participants']);

        // Filter by date range
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->dateRange($request->start_date, $request->end_date);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Filter for specific user
        if ($request->has('user_id')) {
            $query->forUser($request->user_id);
        }

        // Get upcoming schedules
        if ($request->has('upcoming') && $request->upcoming) {
            $query->upcoming();
        }

        // Get today's schedules
        if ($request->has('today') && $request->today) {
            $query->today();
        }

        $perPage = $request->get('per_page', 10);
        $schedules = $query->latest('date')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $schedules
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'location' => 'nullable|string',
            'type' => 'required|in:meeting,training,event,other',
            'participant_ids' => 'nullable|array',
            'participant_ids.*' => 'exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $schedule = Schedule::create([
                'title' => $request->title,
                'description' => $request->description,
                'date' => $request->date,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'location' => $request->location,
                'type' => $request->type,
                'status' => 'scheduled',
                'created_by' => auth()->id(),
            ]);

            // Add participants
            if ($request->has('participant_ids')) {
                foreach ($request->participant_ids as $userId) {
                    $schedule->addParticipant($userId);
                    
                    // Send notification
                    Notification::notifySchedule($userId, $schedule);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Jadwal berhasil ditambahkan',
                'data' => $schedule->load('participants')
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $schedule = Schedule::with(['creator', 'participants', 'attendances.user'])->find($id);

        if (!$schedule) {
            return response()->json([
                'success' => false,
                'message' => 'Jadwal tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $schedule
        ]);
    }

    public function update(Request $request, $id)
    {
        $schedule = Schedule::find($id);

        if (!$schedule) {
            return response()->json([
                'success' => false,
                'message' => 'Jadwal tidak ditemukan'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'date' => 'sometimes|date',
            'start_time' => 'sometimes|date_format:H:i',
            'end_time' => 'sometimes|date_format:H:i|after:start_time',
            'location' => 'nullable|string',
            'type' => 'sometimes|in:meeting,training,event,other',
            'status' => 'sometimes|in:scheduled,ongoing,completed,cancelled',
            'participant_ids' => 'nullable|array',
            'participant_ids.*' => 'exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $schedule->update($request->except('participant_ids'));

            // Update participants if provided
            if ($request->has('participant_ids')) {
                $schedule->participants()->sync([]);
                foreach ($request->participant_ids as $userId) {
                    $schedule->addParticipant($userId);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Jadwal berhasil diupdate',
                'data' => $schedule->load('participants')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        $schedule = Schedule::find($id);

        if (!$schedule) {
            return response()->json([
                'success' => false,
                'message' => 'Jadwal tidak ditemukan'
            ], 404);
        }

        $schedule->delete();

        return response()->json([
            'success' => true,
            'message' => 'Jadwal berhasil dihapus'
        ]);
    }

    public function mySchedules(Request $request)
    {
        $user = auth()->user();
        $query = Schedule::forUser($user->id)->with('creator');

        if ($request->has('upcoming') && $request->upcoming) {
            $query->upcoming();
        }

        if ($request->has('today') && $request->today) {
            $query->today();
        }

        $schedules = $query->latest('date')->get();

        return response()->json([
            'success' => true,
            'data' => $schedules
        ]);
    }
    public function todayActiveSchedules(Request $request)  // ✅ Ganti nama ini (dari todayActive)
    {
        $user = $request->user();
        $today = now()->format('Y-m-d');

        // Base query jadwal hari ini & aktif
        $query = Schedule::whereDate('date', $today)
            ->where('is_active', true);

        // Jika user anggota → hanya jadwal yang dia ikuti
        if ($user->role == 'anggota') {
            $query->whereHas('participants', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }

        $schedules = $query->orderBy('start_time')->get();

        return response()->json([
            'success' => true,
            'message' => 'Jadwal aktif hari ini berhasil diambil',
            'data' => $schedules,
        ]);
    }

}