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

        try {
            // ✅ CEK JUMLAH SAMPLE YANG SUDAH ADA
            $existingCount = FaceData::where('user_id', $request->user_id)->count();
            
            if ($existingCount >= 5) {
                return response()->json([
                    'success' => false,
                    'message' => 'Maksimal 5 sample wajah per user. Silakan hapus sample lama terlebih dahulu.',
                ], 400);
            }

            $file = $request->file('photo');
            $binary = file_get_contents($file->getRealPath());

            $response = Http::timeout(15)
                ->attach('image', $binary, 'photo.jpg')
                ->post(env('PYTHON_FACE_API_URL') . '/encode');

            \Log::info('Python /encode response:', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            if (!$response->successful()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Face API error: ' . $response->body(),
                ], 500);
            }

            $body = $response->json();

            if (!isset($body['success']) || !$body['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $body['message'] ?? 'Encoding gagal.',
                ], 400);
            }

            if (!isset($body['data']['embedding'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Embedding tidak ditemukan.',
                ], 500);
            }

            $embedding = $body['data']['embedding'];
            $quality = $body['data']['quality_score'] ?? 1.0;
            $method = $body['data']['method'] ?? 'LBPH'; // ✅ TAMBAHAN
            $path = $file->store('face_photos', 'public');

            // Sample number
            $sampleNumber = $existingCount + 1;
            $isPrimary = ($sampleNumber === 1);

            // ✅ FIXED: Validasi ukuran embedding LBPH (200x200 = 40,000 elements)
            $expectedSize = 40000; // 200 * 200 pixels
            if (count($embedding) !== $expectedSize) {
                \Log::warning('Embedding size mismatch', [
                    'expected' => $expectedSize,
                    'actual' => count($embedding)
                ]);
            }

            // Create new record
            FaceData::create([
                'user_id' => $request->user_id,
                'face_encoding' => json_encode($embedding),
                'face_photo' => $path,
                'face_sample_number' => $sampleNumber,
                'face_registered_at' => now(),
                'quality_score' => $quality,
                'is_primary' => $isPrimary,
                'is_active' => true,
                'registration_source' => 'admin_panel',
            ]);

            return response()->json([
                'success' => true,
                'message' => "Sample wajah ke-{$sampleNumber} berhasil ditambahkan menggunakan {$method}! ({$sampleNumber}/5)",
                'data' => [
                    'sample_number' => $sampleNumber,
                    'method' => $method,
                    'embedding_size' => count($embedding),
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function register(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'photo'   => 'required',
        ]);

        try {
            // Decode Base64
            $img = $request->photo;
            $img = str_replace(['data:image/jpeg;base64,', 'data:image/png;base64,'], '', $img);
            $img = base64_decode($img);

            $fileName = 'face_' . time() . '_' . $request->user_id . '.jpg';
            $filePath = storage_path('app/public/face/' . $fileName);
            
            if (!file_exists(dirname($filePath))) {
                mkdir(dirname($filePath), 0755, true);
            }
            
            file_put_contents($filePath, $img);
            $photoUrl = 'face/' . $fileName;

            // Kirim ke Python
            $pythonUrl = env('PYTHON_FACE_API_URL') . '/encode';
            
            $response = Http::timeout(15)
                ->attach('image', file_get_contents($filePath), $fileName)
                ->post($pythonUrl);

            \Log::info('Python response:', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            if (!$response->successful()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Python API error: ' . $response->body(),
                ], 500);
            }

            $result = $response->json();

            if (!($result['success'] ?? false)) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'Face encode gagal.',
                ], 400);
            }

            // ✅ FIX: Ambil dari "data"
            $embedding = $result['data']['embedding'] ?? null;
            $quality = $result['data']['quality_score'] ?? 1.0;

            if (!$embedding) {
                return response()->json([
                    'success' => false,
                    'message' => 'Embedding tidak ditemukan.',
                ], 500);
            }

            if ($quality < 0.25) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kualitas wajah terlalu rendah! Coba ambil ulang.',
                ], 400);
            }

            FaceData::create([
                'user_id' => $request->user_id,
                'face_encoding' => json_encode($embedding),
                'face_photo' => $photoUrl,
                'registration_photo' => $request->photo,
                'face_registered_at' => now(),
                'quality_score' => $quality,
                'is_primary' => true,
                'registration_source' => 'admin_panel'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Wajah berhasil diregistrasi!'
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Error register: ' . $e->getMessage());
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
                'message' => 'Coba Periksa Face API.'
            ], 500);
        }

        $body = $response->json();

        if (!($body['success'] ?? false)) {
            return response()->json([
                'success' => false,
                'message' => $body['message'] ?? 'Wajah tidak dikenali.'
            ]);
        }

        // ✅ FIXED: Ambil data dari structure yang benar
        $userId = $body['data']['user_id'];
        $confidence = $body['data']['confidence']; // Percentage 0-100
        $distance = $body['data']['distance']; // LBPH distance
        $method = $body['data']['method'] ?? 'LBPH';

        $user = User::find($userId);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User tidak ditemukan.'
            ]);
        }

        // ✅ SUCCESS Response dengan informasi lengkap
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'employee_id' => $user->employee_id,
                'confidence' => $confidence, // Percentage
                'distance' => $distance, // LBPH distance
                'method' => $method, // Recognition method
                
                // ✅ Info tambahan untuk UI
                'quality' => $distance <= 20 ? 'Excellent' : ($distance <= 30 ? 'Good' : 'Fair'),
            ]
        ]);
    }


    /**
     * ✅ METHOD BARU: Check Attendance Status
     * Mengecek apakah user sudah check-in/out untuk schedule tertentu
     */
    public function checkAttendanceStatus(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'schedule_id' => 'required|exists:schedules,id',
        ]);

        $userId = $request->user_id;
        $scheduleId = $request->schedule_id;
        $today = now()->format('Y-m-d');

        // Cari attendance untuk user ini hari ini dengan schedule yang sama
        $attendance = Attendance::where('user_id', $userId)
            ->where('date', $today)
            ->where('schedule_id', $scheduleId)
            ->first();

        // Default response
        $response = [
            'success' => true,
            'has_checked_in' => false,
            'has_checked_out' => false,
            'can_check_in' => true,
            'can_check_out' => false,
            'check_in_time' => null,
            'check_out_time' => null,
        ];

        if ($attendance) {
            // User punya attendance record untuk schedule ini
            $response['has_checked_in'] = !empty($attendance->check_in_time);
            $response['has_checked_out'] = !empty($attendance->check_out_time);
            $response['check_in_time'] = $attendance->check_in_time;
            $response['check_out_time'] = $attendance->check_out_time;

            // Logika can_check_in dan can_check_out
            if ($response['has_checked_out']) {
                // Sudah lengkap check-in dan check-out
                $response['can_check_in'] = false;
                $response['can_check_out'] = false;
            } elseif ($response['has_checked_in']) {
                // Sudah check-in, belum check-out
                $response['can_check_in'] = false;
                $response['can_check_out'] = true;
            } else {
                // Belum check-in
                $response['can_check_in'] = true;
                $response['can_check_out'] = false;
            }
        } else {
            // Belum ada record sama sekali untuk schedule ini
            // Tapi cek apakah dia sudah check-in di schedule LAIN hari ini
            $otherAttendance = Attendance::where('user_id', $userId)
                ->where('date', $today)
                ->whereNotNull('check_in_time')
                ->where('schedule_id', '!=', $scheduleId)
                ->first();

            if ($otherAttendance) {
                // User sudah check-in di schedule lain
                $response['can_check_in'] = false;
                $response['can_check_out'] = false;
                $response['has_checked_in'] = true; // untuk trigger alert
            }
        }

        return response()->json($response);
    }


    /**
     * ✅ FIXED: Save Attendance with Schedule Validation
     */
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
        $type = $request->type;

        // Decode Base64 Photo
        $photoData = $request->photo;
        $photoData = preg_replace('/^data:image\/\w+;base64,/', '', $photoData);
        $photoPath = 'attendance/face_' . $userId . '_' . time() . '.jpg';
        Storage::disk('public')->put($photoPath, base64_decode($photoData));

        // ✅ FIX: Gunakan Carbon untuk date yang konsisten
        $today = Carbon::now()->format('Y-m-d'); // Format: 2025-12-21
        $nowTime = Carbon::now()->format('H:i:s'); // Format: 14:22:01

        // ✅ PERBAIKAN: Cari atau buat attendance BERDASARKAN schedule_id
        $attendance = Attendance::where('user_id', $userId)
            ->whereDate('date', $today) // ✅ Gunakan whereDate untuk avoid time issue
            ->where('schedule_id', $scheduleId)
            ->first();

        // Jika belum ada record untuk schedule ini, buat baru
        if (!$attendance) {
            $attendance = new Attendance();
            $attendance->user_id = $userId;
            $attendance->date = $today; // ✅ Simpan hanya date, bukan datetime
            $attendance->schedule_id = $scheduleId;
        }

        // ✅ VALIDASI: Check-In
        if ($type == 'check_in') {
            // Cek apakah sudah check-in
            if ($attendance->check_in_time) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda sudah melakukan check-in untuk jadwal ini pada ' . $attendance->check_in_time
                ], 400);
            }

            // Cek apakah sudah check-in di schedule lain hari ini
            $otherAttendance = Attendance::where('user_id', $userId)
                ->whereDate('date', $today)
                ->whereNotNull('check_in_time')
                ->where('schedule_id', '!=', $scheduleId)
                ->first();

            if ($otherAttendance) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda sudah check-in di jadwal lain. Silakan check-out terlebih dahulu.'
                ], 400);
            }

            // Simpan check-in
            $attendance->check_in_time = $nowTime;
            $attendance->check_in_photo = $photoPath;
            $attendance->check_in_confidence = 1;
            $attendance->check_in_face_verified = true;
            $attendance->status = 'present';
            $attendance->save();

            $schedule = Schedule::find($scheduleId);
            
            return response()->json([
                'success' => true,
                'message' => '<strong>Check-In Berhasil!</strong><br>' . 
                            'Waktu: ' . $nowTime . '<br>' .
                            'Jadwal: ' . ($schedule ? $schedule->title : 'N/A')
            ]);
        }

        // ✅ VALIDASI: Check-Out
        if ($type == 'check_out') {
            // Cek apakah record attendance ada
            if (!$attendance || !$attendance->exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda belum melakukan check-in untuk jadwal ini.'
                ], 400);
            }

            // Cek apakah sudah check-in
            if (!$attendance->check_in_time) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda harus check-in terlebih dahulu sebelum check-out.'
                ], 400);
            }

            // Cek apakah sudah check-out
            if ($attendance->check_out_time) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda sudah melakukan check-out untuk jadwal ini pada ' . $attendance->check_out_time
                ], 400);
            }

            // Simpan check-out
            $attendance->check_out_time = $nowTime;
            $attendance->check_out_photo = $photoPath;
            $attendance->check_out_confidence = 1;
            $attendance->check_out_face_verified = true;

            // ✅ FIX: Calculate work duration dengan format yang benar
            if ($attendance->check_in_time) {
                try {
                    // Gabungkan date + time dengan cara yang benar
                    $dateOnly = Carbon::parse($attendance->date)->format('Y-m-d');
                    $checkInDateTime = Carbon::createFromFormat('Y-m-d H:i:s', $dateOnly . ' ' . $attendance->check_in_time);
                    $checkOutDateTime = Carbon::createFromFormat('Y-m-d H:i:s', $dateOnly . ' ' . $nowTime);
                    
                    $attendance->work_duration = $checkInDateTime->diffInMinutes($checkOutDateTime);
                } catch (\Exception $e) {
                    // Jika parsing gagal, hitung manual
                    \Log::error('Error calculating work duration: ' . $e->getMessage());
                    $attendance->work_duration = 0;
                }
            }

            $attendance->save();

            return response()->json([
                'success' => true,
                'message' => '<strong>Check-Out Berhasil!</strong><br>' . 
                            'Waktu: ' . $nowTime . '<br>' .
                            'Durasi Kerja: ' . ($attendance->work_duration ?? 0) . ' menit'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Tipe absensi tidak valid.'
        ], 400);
    }
    
    public function faceRegisterView()
    {
        $users = User::orderBy('name')->get();
        
        return view('admin.attendances.face-register', compact('users'));
    }
    }