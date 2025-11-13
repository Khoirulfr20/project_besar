<?php
// ============================================
// File: app/Http/Controllers/Api/ReportController.php
// ============================================

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $query = Report::with('generator');

        if (auth()->user()->role !== 'admin') {
            $query->where('generated_by', auth()->id());
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $reports = $query->latest()->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $reports
        ]);
    }

    public function generate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'type' => 'required|in:daily,weekly,monthly,custom',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'format' => 'required|in:pdf,excel,csv',
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'exists:users,id',
            'departments' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Create report record
        $report = Report::create([
            'title' => $request->title,
            'type' => $request->type,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'format' => $request->format,
            'filters' => [
                'user_ids' => $request->user_ids,
                'departments' => $request->departments,
            ],
            'generated_by' => auth()->id(),
            'status' => 'pending',
        ]);

        // Generate report data (simplified version)
        try {
            $data = $this->generateReportData($request);
            
            // In production, you would generate actual PDF/Excel file here
            // For now, we'll just mark as completed with summary
            
            $report->markAsCompleted(
                'reports/report_' . $report->id . '.' . $request->format,
                1024, // dummy file size
                $data['summary']
            );

            return response()->json([
                'success' => true,
                'message' => 'Report berhasil dibuat',
                'data' => $report
            ], 201);
        } catch (\Exception $e) {
            $report->markAsFailed($e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat report: ' . $e->getMessage()
            ], 500);
        }
    }

    private function generateReportData($request)
    {
        $query = Attendance::with('user')
            ->whereBetween('date', [$request->start_date, $request->end_date]);

        if ($request->has('user_ids')) {
            $query->whereIn('user_id', $request->user_ids);
        }

        if ($request->has('departments')) {
            $query->whereHas('user', function($q) use ($request) {
                $q->whereIn('department', $request->departments);
            });
        }

        $attendances = $query->get();

        $summary = [
            'total_records' => $attendances->count(),
            'present' => $attendances->where('status', 'present')->count(),
            'late' => $attendances->where('status', 'late')->count(),
            'absent' => $attendances->where('status', 'absent')->count(),
            'excused' => $attendances->where('status', 'excused')->count(),
            'leave' => $attendances->where('status', 'leave')->count(),
        ];

        return [
            'attendances' => $attendances,
            'summary' => $summary
        ];
    }

    public function download($id)
    {
        $report = Report::find($id);

        if (!$report) {
            return response()->json([
                'success' => false,
                'message' => 'Report tidak ditemukan'
            ], 404);
        }

        if ($report->status !== 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'Report belum selesai dibuat'
            ], 400);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'download_url' => $report->download_url,
                'file_size' => $report->file_size_formatted
            ]
        ]);
    }

    public function summary(Request $request)
    {
        $startDate = $request->get('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->get('end_date', now()->toDateString());

        // ✅ Fix: Gunakan query builder biasa tanpa scope
        $totalUsers = User::where('role', 'karyawan')->count();
        
        // ✅ Fix: Gunakan whereBetween langsung
        $attendances = Attendance::whereBetween('date', [$startDate, $endDate])->get();

        $summary = [
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'total_users' => $totalUsers,
            'total_attendances' => $attendances->count(),
            'present' => $attendances->where('status', 'present')->count(),
            'late' => $attendances->where('status', 'late')->count(),
            'absent' => $attendances->where('status', 'absent')->count(),
            'excused' => $attendances->where('status', 'excused')->count(),
            'leave' => $attendances->where('status', 'leave')->count(),
            'attendance_rate' => 0,
        ];

        // ✅ Fix: Hitung hari kerja dengan benar
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        
        $workingDays = 0;
        $current = $start->copy();
        
        while ($current->lte($end)) {
            if ($current->isWeekday()) {
                $workingDays++;
            }
            $current->addDay();
        }

        $expectedAttendances = $totalUsers * $workingDays;
        if ($expectedAttendances > 0) {
            $presentCount = $summary['present'] + $summary['late'];
            $summary['attendance_rate'] = round(($presentCount / $expectedAttendances) * 100, 2);
        }

        return response()->json([
            'success' => true,
            'data' => $summary
        ]);
    }
}