<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\AttendanceLog;
use App\Models\User;
use App\Models\Schedule;
use App\Models\FaceData; 
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class AdminAttendanceFaceController extends Controller
{
    /**
     * Manual Attendance (Admin Input Page)
     */
    public function record()
    {
        $users = \App\Models\User::where('is_active', true)->orderBy('name')->get();
        $schedules = \App\Models\Schedule::orderBy('date', 'desc')->get();

        $todayAttendances = \App\Models\Attendance::with('user')
            ->whereDate('date', now()->toDateString())
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.attendances.record', compact('users', 'schedules', 'todayAttendances'));
    }

    /**
     * Manual Save (Admin chooses user + upload photo)
     */
    public function storeManual(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'date' => 'required|date',
            'type' => 'required|in:check_in,check_out',
            'time' => 'required',
            'status' => 'required|in:present,late,absent,excused,leave',
            'photo' => 'required|string',
        ]);

        $userId = $request->user_id;
        $attendance = Attendance::firstOrNew([
            'user_id' => $userId,
            'date' => $request->date,
        ]);

        // Save Base64 Photo
        $photoPath = $this->saveBase64($request->photo, $userId);

        if ($request->type == 'check_in') {
            $attendance->check_in_time = $request->time;
            $attendance->check_in_photo = $photoPath;
            $attendance->check_in_confidence = 1;
            $attendance->check_in_device = 'Admin Web';
        } else {
            $attendance->check_out_time = $request->time;
            $attendance->check_out_photo = $photoPath;
            $attendance->check_out_confidence = 1;
            $attendance->check_out_device = 'Admin Web';

            // Calculate duration
            if ($attendance->check_in_time) {
                $attendance->work_duration = Carbon::parse($attendance->check_in_time)
                    ->diffInMinutes(Carbon::parse($request->time));
            }
        }

        $attendance->status = $request->status;
        $attendance->schedule_id = $request->schedule_id;
        $attendance->notes = $request->notes;
        $attendance->save();

        AttendanceLog::createLog(
            $attendance->id,
            auth()->id(),
            $request->type,
            'Manual record by admin'
        );

        return response()->json(['success' => true, 'message' => 'Kehadiran berhasil disimpan']);
    }

    /**
     * Send Photo to Python Server for Recognition
     */
    private function sendToPython($photo)
    {
        return Http::timeout(10)->attach(
            'photo', file_get_contents($photo->getRealPath()), $photo->getClientOriginalName()
        )->post(env('PYTHON_FACE_API_URL') . '/verify');
    }

    /**
     * Check In via Face Recognition
     */
    public function faceCheckIn(Request $request)
    {
        $request->validate(['photo' => 'required|image|max:5120']);

        $response = $this->sendToPython($request->file('photo'));

        if (!$response->successful()) {
            return response()->json(['success' => false, 'message' => 'Face server error'], 500);
        }

        $result = $response->json();

        if (!$result['status']) {
            return response()->json(['success' => false, 'message' => 'Wajah tidak dikenali'], 400);
        }

        return $this->saveFaceAttendance($result['user_id'], 'check_in', $request->file('photo'));
    }

    /**
     * Check Out via Face Recognition
     */
    public function faceCheckOut(Request $request)
    {
        $request->validate(['photo' => 'required|image|max:5120']);

        $response = $this->sendToPython($request->file('photo'));

        if (!$response->successful()) {
            return response()->json(['success' => false, 'message' => 'Face server error'], 500);
        }

        $result = $response->json();

        if (!$result['status']) {
            return response()->json(['success' => false, 'message' => 'Wajah tidak dikenali'], 400);
        }

        return $this->saveFaceAttendance($result['user_id'], 'check_out', $request->file('photo'));
    }

    /**
     * Save face attendance to DB
     */
    private function saveFaceAttendance($userId, $type, $photo)
    {
        $today = now()->toDateString();
        $attendance = Attendance::firstOrNew(['user_id' => $userId, 'date' => $today]);

        // Save image file
        $path = $photo->store('attendance/face', 'public');

        if ($type == 'check_in' && !$attendance->check_in_time) {
            $attendance->check_in_time = now()->format('H:i:s');
            $attendance->check_in_photo = $path;
            $attendance->check_in_confidence = 1;
            $attendance->check_in_device = 'Admin Face Recognition';
        } elseif ($type == 'check_out' && !$attendance->check_out_time) {
            $attendance->check_out_time = now()->format('H:i:s');
            $attendance->check_out_photo = $path;
            $attendance->check_out_confidence = 1;
            $attendance->check_out_device = 'Admin Face Recognition';

            // Calculate work duration
            if ($attendance->check_in_time) {
                $attendance->work_duration = Carbon::parse($attendance->date . ' ' . $attendance->check_in_time)
                    ->diffInMinutes(now());
            }
        }

        $attendance->status = 'present';
        $attendance->save();

        AttendanceLog::createLog(
            $attendance->id,
            $userId,
            $type,
            'Face recorded via Admin Panel'
        );

        return response()->json(['success' => true, 'message' => 'Absensi wajah berhasil disimpan']);
    }

    /**
     * Save Base64 Image (Manual Upload)
     */
    private function saveBase64($base64, $userId)
    {
        $imageData = preg_replace('/^data:image\/\w+;base64,/', '', $base64);
        $imageData = str_replace(' ', '+', $imageData);
        $imageName = 'attendance_' . $userId . '_' . time() . '.jpg';
        Storage::disk('public')->put('attendance/' . $imageName, base64_decode($imageData));
        return 'attendance/' . $imageName;
    }

    public function faceRecord()
    {
        $today = now()->format('Y-m-d');

        // daftar user untuk dropdown registrasi
        $users = User::orderBy('name')->get();

        // Absensi hari ini
        $todayAttendances = Attendance::whereDate('date', $today)->get();

        // Jadwal hari ini
        $todaySchedules = Schedule::whereDate('date', $today)
            ->where('is_active', true)
            ->orderBy('start_time')
            ->get();

        return view('admin.attendances.face-record', compact(
            'users',
            'todayAttendances',
            'todaySchedules'
        ));
    }


   public function faceRegister(Request $request)
{
    $request->validate([
        'user_id' => 'required|exists:users,id',
        'photo'   => 'required|file|image',
    ]);

    $file = $request->file('photo');
    $binary = file_get_contents($file->getRealPath());

    $response = Http::timeout(10)
        ->attach('image', $binary, 'photo.jpg')
        ->post(config('services.face_api.url') . '/encode');

    if (! $response->ok()) {
        return response()->json([
            'success' => false,
            'message' => 'Face API tidak dapat diakses.',
        ], 500);
    }

    $body = $response->json();
    if (!($body['success'] ?? false)) {
        return response()->json(['success' => false, 'message' => $body['message'] ?? 'Encoding gagal.']);
    }

    $embedding = $body['embedding'];
    $quality   = $body['quality_score'];

    // simpan foto fisik untuk bukti
    $path = $file->store('face_photos', 'public');

    FaceData::updateOrCreate(
        ['user_id' => $request->user_id, 'is_primary' => true],
        [
            'face_encoding'      => json_encode($embedding),
            'face_photo'         => $path,
            'face_registered_at' => now(),
            'quality_score'      => $quality,
            'is_active'          => true,
            'registration_source'=> 'admin_panel',
        ]
    );

    return response()->json(['success' => true, 'message' => 'Wajah berhasil diregistrasi!']);
}


public function register(Request $request)
{
    $request->validate([
        'user_id' => 'required|exists:users,id',
        'photo'   => 'required',
    ]);

    try {
        /** =====================================================
         * 1) Decode Base64 & Simpan FOTO ke Storage
         * ===================================================== */
        $img = $request->photo; // Base64
        $img = str_replace('data:image/jpeg;base64,', '', $img);
        $img = str_replace('data:image/png;base64,', '', $img);
        $img = base64_decode($img);

        // Simpan file fisik
        $fileName = 'face_' . time() . '_' . $request->user_id . '.jpg';
        $filePath = storage_path('app/public/face/' . $fileName);
        file_put_contents($filePath, $img);

        // URL file untuk DB
        $photoUrl = 'face/' . $fileName;

        /** =====================================================
         * 2) Kirim foto ke Python: /encode
         * ===================================================== */
        $pythonUrl = env('PYTHON_RECOG_URL') . '/encode'; // contoh: http://127.0.0.1:8001

        $response = Http::timeout(10)->attach(
            'image',
            file_get_contents($filePath),
            $fileName
        )->post($pythonUrl);

        if (!$response->json('success')) {
            return response()->json([
                'success' => false,
                'message' => $response->json('message') ?? 'Face encode gagal.',
            ], 200);
        }

        $embedding = $response->json('embedding');
        $quality   = $response->json('quality_score');

        // Jika angka kualitas terlalu kecil, tolak dulu
        if ($quality < 0.25) {
            return response()->json([
                'success' => false,
                'message' => 'Kualitas wajah terlalu rendah! Coba ambil ulang.',
            ], 200);
        }

        /** =====================================================
         * 3) Simpan ke DB face_data
         * ===================================================== */
        FaceData::create([
            'user_id'             => $request->user_id,
            'face_encoding'       => json_encode($embedding),
            'face_photo'          => $photoUrl,
            'registration_photo'  => $request->photo,
            'face_registered_at'  => now(),
            'quality_score'       => $quality,
            'is_primary'          => true,
            'registration_source' => 'admin_panel'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Wajah berhasil diregistrasi!'
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ], 500);
    }
}



public function faceRecognize(Request $request)
{
    $request->validate([
        'photo' => 'required|file|image'
    ]);

    $file = $request->file('photo');
    $binary = file_get_contents($file->getRealPath());

    $response = Http::timeout(10)
        ->attach('image', $binary, 'photo.jpg')
        ->post(env('PYTHON_FACE_API_URL') . '/recognize');

    if (! $response->ok()) {
        return response()->json([
            'success' => false,
            'message' => 'Tidak dapat menghubungi Face API.'
        ], 500);
    }

    $body = $response->json();

    if (!($body['success'] ?? false)) {
        return response()->json([
            'success' => false,
            'message' => $body['message'] ?? 'Wajah tidak dikenali.'
        ]);
    }

    $userId     = $body['data']['user_id'];
    $confidence = $body['data']['confidence'];

    $user = User::find($userId);

    if (!$user) {
        return response()->json([
            'success' => false,
            'message' => 'User tidak ditemukan.'
        ]);
    }

    return response()->json([
        'success' => true,
        'data' => [
            'id' => $user->id,
            'name' => $user->name,
            'employee_id' => $user->employee_id,
            'confidence' => $confidence
        ]
    ]);
}




public function faceSaveAttendance(Request $request)
{
    $request->validate([
        'recognized_user_id' => 'required|exists:users,id',
        'schedule_id' => 'required|exists:schedules,id',
        'type' => 'required|in:check_in,check_out',
        'photo' => 'required',
    ]);

    $userId = $request->recognized_user_id;
    $scheduleId = $request->schedule_id;

    // Decode Base64 Photo
    $photoData = $request->photo;
    $photoData = preg_replace('/^data:image\/\w+;base64,/', '', $photoData);
    $photoPath = 'attendance/face_' . $userId . '_' . time() . '.jpg';
    Storage::disk('public')->put($photoPath, base64_decode($photoData));

    $today = now()->format('Y-m-d');
    $attendance = Attendance::firstOrNew(['user_id' => $userId, 'date' => $today]);
    $nowTime = now()->format('H:i:s');

    if ($request->type == 'check_in' && !$attendance->check_in_time) {
        $attendance->schedule_id = $scheduleId;
        $attendance->check_in_time = $nowTime;
        $attendance->check_in_photo = $photoPath;
        $attendance->check_in_confidence = 1;
        $attendance->check_in_face_verified = true;
        $attendance->status = 'present';
    } 
    elseif ($request->type == 'check_out' && !$attendance->check_out_time) {
        $attendance->check_out_time = $nowTime;
        $attendance->check_out_photo = $photoPath;
        $attendance->check_out_confidence = 1;
        $attendance->check_out_face_verified = true;

        if ($attendance->check_in_time) {
            $attendance->work_duration = Carbon::parse($attendance->date . ' ' . $attendance->check_in_time)
                ->diffInMinutes(now());
        }
    }

    $attendance->save();

    return response()->json([
        'success' => true,
        'message' => 'Kehadiran berhasil disimpan.'
    ]);
}


}