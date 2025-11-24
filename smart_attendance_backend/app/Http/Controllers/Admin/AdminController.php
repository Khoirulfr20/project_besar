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
    // Dashboard
    public function dashboard()
    {
        $totalUsers = User::active()->count();
        $todayPresent = Attendance::today()->present()->count();
        $todayLate = Attendance::today()->late()->count();
        $todayAbsent = Attendance::today()->absent()->count();
        $todaySchedules = Schedule::today()->with('participants')->get();
        $latestAttendances = Attendance::today()->with('user')->latest()->take(10)->get();
        
        // Weekly statistics
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
            'totalUsers', 'todayPresent', 'todayLate', 'todayAbsent',
            'todaySchedules', 'latestAttendances',
            'weeklyLabels', 'weeklyPresent', 'weeklyLate', 'weeklyAbsent'
        ));
    }

    // ============================================
    // Users Management
    // ============================================
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
        
        $data = $request->except('photo');
        $data['password'] = Hash::make($request->password);
        
        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('users', 'public');
        }
        
        User::create($data);
        return redirect()->route('admin.users.index')->with('success', 'User berhasil ditambahkan');
    }
    
    public function usersDestroy($id)
    {
        User::findOrFail($id)->delete();
        return redirect()->route('admin.users.index')->with('success', 'User berhasil dihapus');
    }

    // ============================================
    // Schedules Management
    // ============================================
    public function schedulesIndex()
    {
        $schedules = Schedule::with('participants')->latest('date')->get();
        return view('admin.schedules.index', compact('schedules'));
    }
    
    public function schedulesCreate()
    {
        $users = User::active()->get();
        return view('admin.schedules.create', compact('users'));
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

    // ============================================
    // Attendances Management (FIXED)
    // ============================================
    public function attendancesIndex(Request $request)
    {
        $date = $request->get('date', now()->format('Y-m-d'));
        $status = $request->get('status');
        $department = $request->get('department');

        // Query attendances
        $query = Attendance::with('user')
            ->whereDate('date', $date);

        if ($status) {
            $query->where('status', $status);
        }

        if ($department) {
            $query->whereHas('user', function($q) use ($department) {
                $q->where('department', $department);
            });
        }

        $attendances = $query->latest('created_at')->paginate(50);

        // Today's statistics
        $todayStats = [
            'present' => Attendance::whereDate('date', $date)->where('status', 'present')->count(),
            'late' => Attendance::whereDate('date', $date)->where('status', 'late')->count(),
            'excused' => Attendance::whereDate('date', $date)->where('status', 'excused')->count(),
            'absent' => Attendance::whereDate('date', $date)->where('status', 'absent')->count(),
        ];

        // Get departments for filter
        $departments = User::whereNotNull('department')
            ->distinct()
            ->pluck('department')
            ->filter();

        return view('admin.attendances.index', compact(
            'attendances',
            'todayStats',
            'departments'
        ));
    }
    
    public function attendancesUpdateStatus(Request $request, $id)
    {
        $attendance = Attendance::findOrFail($id);
        $attendance->update(['status' => $request->status]);
        
        return response()->json(['success' => true]);
    }

    public function attendancesHistory($id)
    {
        $logs = \App\Models\AttendanceLog::where('attendance_id', $id)
            ->with('user')
            ->latest()
            ->get();
        
        return response()->json($logs);
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

    /**
     * Show Record Attendance Form
     */
    public function recordAttendance()
    {
        $users = User::where('is_active', true)
            ->orderBy('name')
            ->get();
            
        $schedules = Schedule::whereDate('date', now())
            ->where('is_active', true)
            ->get();
            
        $todayAttendances = Attendance::with('user')
            ->whereDate('date', now())
            ->latest()
            ->take(10)
            ->get();

        return view('admin.attendances.record', compact('users', 'schedules', 'todayAttendances'));
    }

    /**
     * Store Manual Attendance Record
     */
    public function storeAttendance(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'date' => 'required|date',
            'type' => 'required|in:check_in,check_out',
            'time' => 'required',
            'status' => 'required|in:present,late,absent,excused,leave',
            'photo' => 'required|string',
        ]);

        try {
            // Find or create attendance record
            $attendance = Attendance::firstOrNew([
                'user_id' => $request->user_id,
                'date' => $request->date,
            ]);

            // Process base64 photo
            $photoPath = null;
            if ($request->photo) {
                $imageData = $request->photo;
                
                // Remove data:image/jpeg;base64, prefix
                $imageData = preg_replace('/^data:image\/\w+;base64,/', '', $imageData);
                $imageData = str_replace(' ', '+', $imageData);
                
                // Generate unique filename
                $imageName = 'attendance_' . $request->user_id . '_' . time() . '.jpg';
                
                // Save to storage
                Storage::disk('public')->put('attendance/' . $imageName, base64_decode($imageData));
                $photoPath = 'attendance/' . $imageName;
            }

            // Update based on attendance type
            if ($request->type == 'check_in') {
                $attendance->check_in_time = $request->time;
                $attendance->check_in_photo = $photoPath;
                $attendance->check_in_confidence = 1.0; // Manual entry = 100% confidence
                $attendance->check_in_location = $request->location;
                $attendance->check_in_device = 'Admin Panel';
            } else {
                $attendance->check_out_time = $request->time;
                $attendance->check_out_photo = $photoPath;
                $attendance->check_out_confidence = 1.0;
                $attendance->check_out_location = $request->location;
                $attendance->check_out_device = 'Admin Panel';
                
                // Calculate work duration if check-in exists
                if ($attendance->check_in_time) {
                    $checkIn = Carbon::parse($attendance->date->format('Y-m-d') . ' ' . $attendance->check_in_time);
                    $checkOut = Carbon::parse($attendance->date->format('Y-m-d') . ' ' . $request->time);
                    $attendance->work_duration = $checkIn->diffInMinutes($checkOut);
                }
            }

            // Update other fields
            $attendance->status = $request->status;
            $attendance->schedule_id = $request->schedule_id;
            $attendance->notes = $request->notes;
            $attendance->save();

            // Create attendance log
            AttendanceLog::create([
                'attendance_id' => $attendance->id,
                'user_id' => auth()->id(),
                'action' => $request->type == 'check_in' ? 'check_in' : 'check_out',
                'description' => 'Manual entry by admin: ' . auth()->user()->name,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Kehadiran berhasil disimpan',
                'data' => [
                    'attendance_id' => $attendance->id,
                    'user' => $attendance->user->name,
                    'type' => $request->type,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk Import Attendance (CSV/Excel)
     */
    public function bulkImportAttendance(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,xlsx,xls',
        ]);

        try {
            // Process bulk import here
            // You can use Laravel Excel package
            
            return response()->json([
                'success' => true,
                'message' => 'Import berhasil'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
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

        // Calculate statistics
        $allQuery = clone $query;
        $allAttendances = $allQuery->get();
        
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

        // Chart data (last 7 days)
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
            'departments'
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