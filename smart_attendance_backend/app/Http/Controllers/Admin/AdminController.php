<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\User;
use App\Models\Schedule;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use PDF;
use Illuminate\Support\Facades\Storage;
use App\Models\AttendanceLog;
use App\Models\Setting;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\AttendanceExport;

class AdminController extends Controller
{
    /**
     * DASHBOARD VIEW
     */
    public function dashboard()
    {
        $totalUsers = User::active()->count();
        $todayPresent = Attendance::today()->present()->count();
        $todayLate = Attendance::today()->late()->count();
        $todayAbsent = Attendance::today()->absent()->count();
        $todaySchedules = Schedule::today()->with('participants')->get();
        $latestAttendances = Attendance::today()->with('user')->latest()->take(10)->get();

        // Chart Data Last 7 Days
        $weeklyLabels = [];
        $weeklyPresent = [];
        $weeklyLate = [];
        $weeklyAbsent = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $weeklyLabels[] = $date->format('D');
            $weeklyPresent[] = Attendance::whereDate('date', $date)->present()->count();
            $weeklyLate[] = Attendance::whereDate('date', $date)->late()->count();
            $weeklyAbsent[] = Attendance::whereDate('date', $date)->absent()->count();
        }

        return view('admin.dashboard.index', compact(
            'totalUsers','todayPresent','todayLate','todayAbsent',
            'todaySchedules','latestAttendances',
            'weeklyLabels','weeklyPresent','weeklyLate','weeklyAbsent'
        ));
    }

    /**
     * USERS MANAGEMENT
     */
    public function usersIndex() { $users = User::all(); return view('admin.users.index', compact('users')); }

    public function usersCreate() { return view('admin.users.create'); }

    public function usersEdit($id) { $user = User::findOrFail($id); return view('admin.users.edit', compact('user')); }

    public function usersStore(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|unique:users',
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
            'role' => 'required|in:admin,pimpinan,anggota',
        ]);

        $data = $request->except('photo');
        $data['password'] = Hash::make($request->password);

        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('users', 'public');
        }

        User::create($data);
        return redirect()->route('admin.users.index')->with('success', 'User berhasil ditambahkan');
    }

    public function usersUpdate(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'role' => 'required|string',
            'status' => 'nullable|boolean',
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
        ];

        if ($request->has('status')) $data['is_active'] = $request->boolean('status');
        if ($request->filled('password')) $data['password'] = Hash::make($request->password);

        $user->update($data);

        return redirect()->route('admin.users.index')->with('success', 'User berhasil diperbarui');
    }

    public function usersDestroy($id)
    {
        $user = User::findOrFail($id);
        if ($user->photo) Storage::disk('public')->delete($user->photo);
        $user->delete();
        return redirect()->route('admin.users.index')->with('success', 'User berhasil dihapus');
    }

    /**
     * SCHEDULES MANAGEMENT
     */
    public function schedulesIndex()
    {
        $schedules = Schedule::with('participants')->latest('date')->get();
        return view('admin.schedules.index', compact('schedules'));
    }

    public function schedulesCreate()
    {
        $users = User::active()->orderBy('name')->get();
        return view('admin.schedules.create', compact('users'));
    }

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

        if ($request->participant_ids) $schedule->participants()->attach($request->participant_ids);

        return redirect()->route('admin.schedules.index')->with('success', 'Jadwal berhasil ditambahkan');
    }

    public function schedulesUpdate(Request $request, $id)
{
    $request->validate([
        'title'       => 'required|string|max:255',
        'description' => 'nullable|string',
        'date'        => 'required|date',
        'start_time'  => 'required',
        'end_time'    => 'required|after:start_time',
        'location'    => 'required|string|max:255',
        'type'        => 'required|in:meeting,training,event,other',
        'status'      => 'required|in:scheduled,ongoing,completed,cancelled',
    ]);

    $schedule = \App\Models\Schedule::findOrFail($id);

    $schedule->update([
        'title'       => $request->title,
        'description' => $request->description,
        'date'        => $request->date,
        'start_time'  => $request->start_time,
        'end_time'    => $request->end_time,
        'location'    => $request->location,
        'type'        => $request->type,
        'status'      => $request->status,
        // kalau kamu punya kolom is_active dan mau diubah via form,
        // tambahkan input di Blade dan aktifkan baris ini:
        // 'is_active'   => $request->boolean('is_active'),
    ]);

    return redirect()
        ->route('admin.schedules.index')
        ->with('success', 'Jadwal berhasil diperbarui');
}


    public function schedulesDestroy($id)
    {
        $schedule = Schedule::findOrFail($id);

        // Jika ada relasi participants, detach dulu untuk mencegah error foreign key
        if ($schedule->participants()) {
            $schedule->participants()->detach();
        }

        $schedule->delete();

        return redirect()
            ->route('admin.schedules.index')
            ->with('success', 'Jadwal berhasil dihapus.');
    }

    /**
     * ATTENDANCE MANAGEMENT (WITHOUT FACE)
     */
    public function attendancesIndex(Request $request)
    {
        $date = $request->get('date', now()->format('Y-m-d'));
        $status = $request->get('status');
        $department = $request->get('department');

        $query = Attendance::with('user')->whereDate('date', $date);

        if ($status) $query->where('status', $status);
        if ($department) $query->whereHas('user', fn($q)=>$q->where('department', $department));

        $attendances = $query->latest('created_at')->paginate(50);

        $todayStats = [
            'present' => Attendance::whereDate('date', $date)->where('status', 'present')->count(),
            'late' => Attendance::whereDate('date', $date)->where('status', 'late')->count(),
            'excused' => Attendance::whereDate('date', $date)->where('status', 'excused')->count(),
            'absent' => Attendance::whereDate('date', $date)->where('status', 'absent')->count(),
        ];

        $departments = User::whereNotNull('department')->distinct()->pluck('department')->filter();
        $users = User::orderBy('name')->get();

        return view('admin.attendances.index', compact('attendances','todayStats','departments','users'));
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:present,late,absent,excused,leave'
        ]);

        $attendance = Attendance::findOrFail($id);
        $attendance->status = $request->status;
        $attendance->save();

        return response()->json([
            'success' => true,
            'message' => 'Status berhasil diperbarui.',
            'status' => $attendance->status
        ], 200);
    }

    public function attendancesHistory($id)
    {
        $user = User::findOrFail($id);
        $attendances = Attendance::where('user_id', $id)->with('user')->latest('date')->paginate(50);

        return view('admin.attendances.history', compact('user', 'attendances'));
    }

    // ============================================
    // History
    // ============================================
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

     // ============================================
    // Attendance Report (with Export)
    // ============================================
    public function attendanceReport(Request $request)
    {
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $department = $request->get('department');
        $status = $request->get('status');

        // Query attendances
        $query = Attendance::with('user')
            ->whereBetween('date', [$startDate, $endDate]);

        if ($department) {
            $query->whereHas('user', function($q) use ($department) {
                $q->where('department', $department);
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        $attendances = $query->latest('date')->paginate(50);

        // Get all users for filter dropdown (FIX)
        $users = User::orderBy('name')->get();

        // Calculate statistics
        $allAttendances = (clone $query)->get();
        
        $statistics = [
            'total' => $allAttendances->count(),
            'present' => $allAttendances->where('status', 'present')->count(),
            'late' => $allAttendances->where('status', 'late')->count(),
            'absent' => $allAttendances->where('status', 'absent')->count(),
            'excused' => $allAttendances->where('status', 'excused')->count(),
            'leave' => $allAttendances->where('status', 'leave')->count(),
        ];

        $presentCount = $statistics['present'] + $statistics['late'];
        $statistics['attendance_rate'] = $statistics['total'] > 0 
            ? ($presentCount / $statistics['total']) * 100 
            : 0;

        // Chart data
        $chartLabels = [];
        $chartPresent = [];
        $chartLate = [];
        $chartAbsent = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::parse($endDate)->subDays($i);
            $chartLabels[] = $date->format('D, d M');
            
            $dayQuery = Attendance::whereDate('date', $date);

            if ($department) {
                $dayQuery->whereHas('user', function($q) use ($department) {
                    $q->where('department', $department);
                });
            }

            $chartPresent[] = (clone $dayQuery)->where('status', 'present')->count();
            $chartLate[] = (clone $dayQuery)->where('status', 'late')->count();
            $chartAbsent[] = (clone $dayQuery)->where('status', 'absent')->count();
        }

        // Get departments for filter
        $departments = User::whereNotNull('department')
            ->distinct()
            ->pluck('department')
            ->filter();

        return view('admin.reports.attendance', compact(
            'attendances',
            'statistics',
            'chartLabels',
            'chartPresent',
            'chartLate',
            'chartAbsent',
            'departments',
            'users'     // 🔥 WAJIB DITAMBAHKAN
        ));
    }


    // ============================================
    // Export Report
    // ============================================
    public function exportReport(Request $request)
    {
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $department = $request->get('department');
        $status = $request->get('status');
        $format = $request->get('export', 'excel');

        // Query data
        $query = Attendance::with('user')
            ->whereBetween('date', [$startDate, $endDate]);

        if ($department) {
            $query->whereHas('user', function($q) use ($department) {
                $q->where('department', $department);
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        $attendances = $query->latest('date')->get();

        // Calculate statistics
        $statistics = [
            'total' => $attendances->count(),
            'present' => $attendances->where('status', 'present')->count(),
            'late' => $attendances->where('status', 'late')->count(),
            'absent' => $attendances->where('status', 'absent')->count(),
            'excused' => $attendances->where('status', 'excused')->count(),
            'leave' => $attendances->where('status', 'leave')->count(),
        ];

        // Export based on format
        switch ($format) {
            case 'pdf':
                return $this->exportPDF($attendances, $statistics, $startDate, $endDate, $department, $status);
            case 'csv':
                return $this->exportCSV($attendances, $statistics, $startDate, $endDate);
            default:
                return $this->exportExcel($attendances, $statistics, $startDate, $endDate);
        }
    }

    private function exportExcel($attendances, $statistics, $startDate, $endDate)
    {
        $fileName = 'Laporan_Kehadiran_' . $startDate . '_sd_' . $endDate . '.xlsx';
        
        return Excel::download(
            new AttendanceExport($attendances, $statistics, $startDate, $endDate),
            $fileName
        );
    }

    private function exportCSV($attendances, $statistics, $startDate, $endDate)
    {
        $fileName = 'Laporan_Kehadiran_' . $startDate . '_sd_' . $endDate . '.csv';
        
        return Excel::download(
            new AttendanceExport($attendances, $statistics, $startDate, $endDate),
            $fileName,
            \Maatwebsite\Excel\Excel::CSV
        );
    }

    private function exportPDF($attendances, $statistics, $startDate, $endDate, $department, $status)
    {
        $fileName = 'Laporan_Kehadiran_' . $startDate . '_sd_' . $endDate . '.pdf';
        
        $pdf = PDF::loadView('admin.reports.attendance-pdf', compact(
            'attendances',
            'statistics',
            'startDate',
            'endDate',
            'department',
            'status'
        ));

        $pdf->setPaper('a4', 'landscape');
        
        return $pdf->download($fileName);
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
}

