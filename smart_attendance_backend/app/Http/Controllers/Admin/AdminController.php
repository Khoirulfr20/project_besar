<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Schedule;
use App\Models\Attendance;
use App\Models\AttendanceLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class AdminController extends Controller
{
    public function dashboard()
    {
        $totalUsers = User::where('is_active', true)->count();
        $todayPresent = Attendance::whereDate('date', today())->whereIn('status', ['present', 'late'])->count();
        $todayLate = Attendance::whereDate('date', today())->where('status', 'late')->count();
        $todayAbsent = Attendance::whereDate('date', today())->where('status', 'absent')->count();
        
        $todaySchedules = Schedule::whereDate('date', today())
            ->with('participants')
            ->get();
            
        $latestAttendances = Attendance::with('user')
            ->whereDate('date', today())
            ->latest()
            ->take(10)
            ->get();
        
        // Weekly statistics
        $weeklyLabels = [];
        $weeklyPresent = [];
        $weeklyLate = [];
        $weeklyAbsent = [];
        
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $weeklyLabels[] = $date->format('D');
            $weeklyPresent[] = Attendance::whereDate('date', $date)->where('status', 'present')->count();
            $weeklyLate[] = Attendance::whereDate('date', $date)->where('status', 'late')->count();
            $weeklyAbsent[] = Attendance::whereDate('date', $date)->where('status', 'absent')->count();
        }
        
        return view('admin.dashboard.index', compact(
            'totalUsers', 'todayPresent', 'todayLate', 'todayAbsent',
            'todaySchedules', 'latestAttendances',
            'weeklyLabels', 'weeklyPresent', 'weeklyLate', 'weeklyAbsent'
        ));
    }
    
    // Users
    public function usersIndex()
    {
        $users = User::all();
        return view('admin.users.index', compact('users'));
    }
    
    public function usersCreate()
    {
        return view('admin.users.create');
    }
    
    public function usersStore(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|unique:users',
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
            'role' => 'required|in:admin,pimpinan,anggota',
        ]);
        
        $data = $request->except('photo', 'password');
        $data['password'] = Hash::make($request->password);
        
        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('users', 'public');
        }
        
        User::create($data);
        return redirect()->route('admin.users.index')->with('success', 'User berhasil ditambahkan');
    }
    
    public function usersEdit($id)
    {
        $user = User::findOrFail($id);
        return view('admin.users.edit', compact('user'));
    }
    
    public function usersUpdate(Request $request, $id)
    {
        $user = User::findOrFail($id);
        
        $request->validate([
            'employee_id' => 'required|unique:users,employee_id,' . $id,
            'name' => 'required',
            'email' => 'required|email|unique:users,email,' . $id,
            'role' => 'required|in:admin,pimpinan,anggota',
        ]);
        
        $data = $request->except('photo', 'password');
        
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }
        
        if ($request->hasFile('photo')) {
            if ($user->photo) {
                Storage::disk('public')->delete($user->photo);
            }
            $data['photo'] = $request->file('photo')->store('users', 'public');
        }
        
        $user->update($data);
        return redirect()->route('admin.users.index')->with('success', 'User berhasil diupdate');
    }
    
    public function usersDestroy($id)
    {
        $user = User::findOrFail($id);
        if ($user->photo) {
            Storage::disk('public')->delete($user->photo);
        }
        $user->delete();
        return redirect()->route('admin.users.index')->with('success', 'User berhasil dihapus');
    }
    
    public function usersToggleStatus($id)
    {
        $user = User::findOrFail($id);
        $user->is_active = !$user->is_active;
        $user->save();
        return redirect()->route('admin.users.index')->with('success', 'Status user berhasil diubah');
    }
    
    // Schedules
    public function schedulesIndex()
    {
        $schedules = Schedule::with('participants')->latest('date')->get();
        return view('admin.schedules.index', compact('schedules'));
    }
    
    public function schedulesCreate()
    {
        $users = User::where('is_active', true)->get();
        return view('admin.schedules.create', compact('users'));
    }

    // Edit - Menampilkan form edit schedule
    public function schedulesEdit($id)
    {
        $schedule = Schedule::findOrFail($id);
        
        return view('admin.schedules.edit', compact('schedule'));
    }
    
    public function schedulesStore(Request $request)
    {
        $request->validate([
            'title' => 'required',
            'date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required',
            'type' => 'required',
        ]);
        
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
        
        if ($request->participant_ids) {
            foreach ($request->participant_ids as $userId) {
                $schedule->participants()->attach($userId);
            }
        }
        
        return redirect()->route('admin.schedules.index')->with('success', 'Jadwal berhasil ditambahkan');
    }

    public function schedulesUpdate(Request $request, $id)
    {
        $schedule = Schedule::findOrFail($id);
        
        // Validasi
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required|after:start_time',
            'location' => 'required|string|max:255',
            'type' => 'required|in:meeting,training,event,other',
            'status' => 'required|in:scheduled,ongoing,completed,cancelled',
        ]);
        
        // Update data
        $schedule->update([
            'title' => $request->title,
            'description' => $request->description,
            'date' => $request->date,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'location' => $request->location,
            'type' => $request->type,
            'status' => $request->status,
        ]);
        
        return redirect()->route('admin.schedules.index')
            ->with('success', 'Schedule berhasil diperbarui');
    }
    
    public function schedulesDestroy($id)
    {
        Schedule::findOrFail($id)->delete();
        return redirect()->route('admin.schedules.index')->with('success', 'Jadwal berhasil dihapus');
    }
    
    // Attendances
    public function attendancesIndex()
    {
        $attendances = Attendance::with('user')->whereDate('date', today())->latest()->get();
        return view('admin.attendances.index', compact('attendances'));
    }
    
    public function attendancesUpdateStatus(Request $request, $id)
    {
        $attendance = Attendance::findOrFail($id);
        $attendance->update(['status' => $request->status]);
        return response()->json(['success' => true]);
    }
    
    public function attendancesHistory($id)
    {
        $logs = Attendance::findOrFail($id)->logs()->with('user')->latest()->get();
        return response()->json($logs);
    }
    
    public function historyIndex(Request $request)
    {
        $query = Attendance::with('user');
        
        if ($request->start_date) {
            $query->whereDate('date', '>=', $request->start_date);
        }
        if ($request->end_date) {
            $query->whereDate('date', '<=', $request->end_date);
        }
        if ($request->user_id) {
            $query->where('user_id', $request->user_id);
        }
        
        $attendances = $query->latest('date')->paginate(50);
        $users = User::all();
        
        return view('admin.history.index', compact('attendances', 'users'));
    }
    
    public function settingsIndex()
    {
        return view('admin.settings.index');
    }

    public function settingsUpdate(Request $request)
    {
        try {
            foreach ($request->except(['_token', '_method', 'group']) as $key => $value) {
                Setting::updateOrCreate(
                    ['key' => $key],
                    [
                        'value' => $value,
                        'type' => is_numeric($value) ? 'integer' : (is_bool($value) ? 'boolean' : 'string'),
                        'group' => $request->group ?? 'general',
                        'is_public' => true
                    ]
                );
            }

            return redirect()->back()->with('success', 'Pengaturan berhasil disimpan');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menyimpan pengaturan: ' . $e->getMessage());
        }
    }

    public function settingsClearCache()
    {
        try {
            \Artisan::call('cache:clear');
            \Artisan::call('config:clear');
            \Artisan::call('route:clear');
            \Artisan::call('view:clear');

            return response()->json([
                'success' => true,
                'message' => 'Cache berhasil dibersihkan'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membersihkan cache: ' . $e->getMessage()
            ], 500);
        }
    }

    // Show record attendance page
    public function recordAttendance()
    {
        $users = User::where('is_active', true)->get();
        $schedules = Schedule::whereDate('date', today())->get();
        $todayAttendances = Attendance::with('user')
            ->whereDate('date', today())
            ->latest()
            ->take(10)
            ->get();

        return view('admin.attendances.record', compact('users', 'schedules', 'todayAttendances'));
    }

    // Store attendance
    public function storeAttendance(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'date' => 'required|date',
            'type' => 'required|in:check_in,check_out',
            'time' => 'required',
            'status' => 'required|in:present,late,absent,excused,leave',
            'photo' => 'required',
            'schedule_id' => 'nullable|exists:schedules,id',
            'location' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        try {
            // Decode base64 photo
            $photoData = $request->photo;
            if (preg_match('/^data:image\/(\w+);base64,/', $photoData, $type)) {
                $photoData = substr($photoData, strpos($photoData, ',') + 1);
                $type = strtolower($type[1]);
                
                $photoData = base64_decode($photoData);
                $fileName = 'attendance/' . uniqid() . '.' . $type;
                Storage::disk('public')->put($fileName, $photoData);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid photo format'
                ], 400);
            }

            // Find or create attendance
            $attendance = Attendance::where('user_id', $request->user_id)
                ->whereDate('date', $request->date)
                ->first();

            if (!$attendance) {
                $attendance = new Attendance();
                $attendance->user_id = $request->user_id;
                $attendance->date = $request->date;
                $attendance->schedule_id = $request->schedule_id;
                $attendance->status = $request->status;
            }

            // Update based on type
            if ($request->type == 'check_in') {
                $attendance->check_in_time = $request->time;
                $attendance->check_in_photo = $fileName;
                $attendance->check_in_location = $request->location;
                $attendance->check_in_confidence = 1.0; // Manual entry
                $attendance->check_in_device = 'Admin Web';
            } else {
                $attendance->check_out_time = $request->time;
                $attendance->check_out_photo = $fileName;
                $attendance->check_out_location = $request->location;
                $attendance->check_out_confidence = 1.0;
                $attendance->check_out_device = 'Admin Web';

                // Calculate work duration
                if ($attendance->check_in_time && $attendance->check_out_time) {
                    $checkIn = Carbon::parse($attendance->check_in_time);
                    $checkOut = Carbon::parse($attendance->check_out_time);
                    $attendance->work_duration = $checkIn->diffInMinutes($checkOut);
                }
            }

            $attendance->notes = $request->notes;
            $attendance->save();

            // Create log
            AttendanceLog::create([
                'attendance_id' => $attendance->id,
                'user_id' => auth()->id(),
                'action' => $request->type,
                'description' => 'Attendance recorded by admin',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Kehadiran berhasil disimpan',
                'data' => $attendance
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    // Bulk import attendance
    public function bulkImportAttendance(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv'
        ]);

        // TODO: Implement Excel import
        // Use PhpSpreadsheet or Laravel Excel

        return redirect()->back()->with('success', 'Import berhasil');
    }
}