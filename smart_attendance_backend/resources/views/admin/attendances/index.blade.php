{{-- ============================================ --}}
{{-- File: resources/views/admin/attendances/index.blade.php --}}
{{-- Kelola Kehadiran Real-time (CRUD & Update Status) --}}
{{-- ============================================ --}}
@extends('layouts.app')
@section('title', 'Kelola Kehadiran')

@section('content')
<div class="d-flex justify-content-between pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Kelola Kehadiran</h1>
    <div>
        <a href="{{ route('admin.reports.attendance') }}" class="btn btn-info">
            <i class="fas fa-chart-bar"></i> Lihat Laporan
        </a>
    </div>
</div>

<!-- Quick Filter -->
<div class="card mb-3">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Pilih Tanggal</label>
                <input type="date" id="filterDate" class="form-control" value="{{ date('Y-m-d') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select id="filterStatus" class="form-select">
                    <option value="">Semua Status</option>
                    <option value="present">Hadir</option>
                    <option value="late">Terlambat</option>
                    <option value="absent">Tidak Hadir</option>
                    <option value="excused">Izin</option>
                    <option value="leave">Cuti</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Departemen</label>
                <select id="filterDepartment" class="form-select">
                    <option value="">Semua Departemen</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept }}">{{ $dept }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">&nbsp;</label>
                <button type="button" class="btn btn-primary w-100" onclick="applyFilter()">
                    <i class="fas fa-filter"></i> Filter
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Statistics Today -->
<div class="row mb-3">
    <div class="col-md-3">
        <div class="card text-white bg-success">
            <div class="card-body text-center">
                <h3 class="mb-0">{{ $todayStats['present'] }}</h3>
                <small>Hadir Hari Ini</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-warning">
            <div class="card-body text-center">
                <h3 class="mb-0">{{ $todayStats['late'] }}</h3>
                <small>Terlambat</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-info">
            <div class="card-body text-center">
                <h3 class="mb-0">{{ $todayStats['excused'] }}</h3>
                <small>Izin</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-danger">
            <div class="card-body text-center">
                <h3 class="mb-0">{{ $todayStats['absent'] }}</h3>
                <small>Tidak Hadir</small>
            </div>
        </div>
    </div>
</div>

<!-- Attendance Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Daftar Kehadiran Hari Ini</h5>
        <span class="badge bg-primary">{{ $attendances->total() }} Data</span>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="attendancesTable" class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th width="5%">No</th>
                        <th width="12%">Tanggal</th>
                        <th width="15%">Nama</th>
                        <th width="10%">Departemen</th>
                        <th width="10%">Check-In</th>
                        <th width="10%">Check-Out</th>
                        <th width="10%">Durasi</th>
                        <th width="13%">Status</th>
                        <th width="15%">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($attendances as $index => $att)
                    <tr>
                        <td>{{ $attendances->firstItem() + $index }}</td>
                        <td>{{ $att->date->format('d/m/Y') }}</td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="avatar me-2">
                                    @if($att->user->photo)
                                        <img src="{{ Storage::url($att->user->photo) }}" 
                                             class="rounded-circle" width="30" height="30">
                                    @else
                                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" 
                                             style="width: 30px; height: 30px;">
                                            {{ substr($att->user->name, 0, 1) }}
                                        </div>
                                    @endif
                                </div>
                                <div>
                                    <strong>{{ $att->user->name }}</strong><br>
                                    <small class="text-muted">{{ $att->user->employee_id }}</small>
                                </div>
                            </div>
                        </td>
                        <td>{{ $att->user->department ?? '-' }}</td>
                        <td>
                            @if($att->check_in_time)
                                <span class="badge bg-success">{{ $att->check_in_time }}</span>
                                @if($att->check_in_photo)
                                    <a href="{{ Storage::url($att->check_in_photo) }}" target="_blank" 
                                       class="btn btn-sm btn-link p-0">
                                        <i class="fas fa-image"></i>
                                    </a>
                                @endif
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            @if($att->check_out_time)
                                <span class="badge bg-info">{{ $att->check_out_time }}</span>
                                @if($att->check_out_photo)
                                    <a href="{{ Storage::url($att->check_out_photo) }}" target="_blank" 
                                       class="btn btn-sm btn-link p-0">
                                        <i class="fas fa-image"></i>
                                    </a>
                                @endif
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            @if($att->work_duration)
                                {{ floor($att->work_duration / 60) }}j {{ $att->work_duration % 60 }}m
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            <select class="form-select form-select-sm status-select" 
                                    data-id="{{ $att->id }}" 
                                    data-original="{{ $att->status }}">
                                <option value="present" {{ $att->status == 'present' ? 'selected' : '' }}>Hadir</option>
                                <option value="late" {{ $att->status == 'late' ? 'selected' : '' }}>Terlambat</option>
                                <option value="absent" {{ $att->status == 'absent' ? 'selected' : '' }}>Alfa</option>
                                <option value="excused" {{ $att->status == 'excused' ? 'selected' : '' }}>Izin</option>
                                <option value="leave" {{ $att->status == 'leave' ? 'selected' : '' }}>Cuti</option>
                            </select>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-warning" onclick="viewHistory({{ $att->id }})" 
                                    title="History">
                                <i class="fas fa-history"></i>
                            </button>
                            @if($att->notes)
                                <button class="btn btn-sm btn-secondary" onclick="viewNotes({{ $att->id }})" 
                                        title="Notes">
                                    <i class="fas fa-sticky-note"></i>
                                </button>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center py-4">
                            <i class="fas fa-inbox fa-3x text-muted mb-3 d-block"></i>
                            <p class="text-muted">Tidak ada data kehadiran hari ini</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="mt-3">
            {{ $attendances->links() }}
        </div>
    </div>
</div>


<!-- History Modal -->
<div class="modal fade" id="historyModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Histori Kehadiran</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="historyContent">
                <div class="text-center">
                    <div class="spinner-border"></div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // DataTable - Disable jika tidak ada data
    if ($('#attendancesTable tbody tr').length > 0 && !$('#attendancesTable tbody tr td').hasClass('text-center')) {
        $('#attendancesTable').DataTable({
            paging: false,
            searching: false,
            ordering: true,
            info: false,
            language: { url: '//cdn.datatables.net/plug-ins/1.13.5/i18n/id.json' }
        });
    }

    // Status Change
    $('.status-select').change(function() {
        const id = $(this).data('id');
        const status = $(this).val();
        const original = $(this).data('original');
        const $select = $(this);
        
        if (confirm('Yakin ubah status kehadiran ini?')) {
            $.ajax({
                url: '/admin/attendances/' + id + '/status',
                method: 'PUT',
                data: { 
                    status: status,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        alert('Status berhasil diubah');
                        $select.data('original', status);
                        location.reload();
                    } else {
                        alert('Gagal mengubah status');
                        $select.val(original);
                    }
                },
                error: function() {
                    alert('Terjadi kesalahan');
                    $select.val(original);
                }
            });
        } else {
            $(this).val(original);
        }
    });
});

// Filter
function applyFilter() {
    const date = $('#filterDate').val();
    const status = $('#filterStatus').val();
    const department = $('#filterDepartment').val();
    
    let url = '{{ route("admin.attendances.index") }}?';
    if (date) url += 'date=' + date + '&';
    if (status) url += 'status=' + status + '&';
    if (department) url += 'department=' + department;
    
    window.location.href = url;
}

// View Detail
function viewDetail(id) {
    $('#detailModal').modal('show');
    $('#detailContent').html('<div class="text-center"><div class="spinner-border"></div></div>');
    
    $.get('/admin/attendances/' + id, function(data) {
        let html = `
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr><th>Nama:</th><td>${data.user.name}</td></tr>
                        <tr><th>Tanggal:</th><td>${data.date}</td></tr>
                        <tr><th>Check-In:</th><td>${data.check_in_time || '-'}</td></tr>
                        <tr><th>Check-Out:</th><td>${data.check_out_time || '-'}</td></tr>
                        <tr><th>Durasi:</th><td>${data.work_duration || '-'}</td></tr>
                    </table>
                </div>
                <div class="col-md-6">
                    ${data.check_in_photo ? `<img src="${data.check_in_photo}" class="img-fluid rounded mb-2" alt="Check-in">` : ''}
                    ${data.check_out_photo ? `<img src="${data.check_out_photo}" class="img-fluid rounded" alt="Check-out">` : ''}
                </div>
            </div>
        `;
        $('#detailContent').html(html);
    });
}

// View History
function viewHistory(id) {
    $('#historyModal').modal('show');
    $('#historyContent').html('<div class="text-center"><div class="spinner-border"></div></div>');
    
    $.get('/admin/attendances/' + id + '/history', function(data) {
        let html = '<div class="list-group">';
        data.forEach(log => {
            html += `<div class="list-group-item">
                <div class="d-flex justify-content-between">
                    <strong>${log.action}</strong>
                    <small class="text-muted">${log.created_at}</small>
                </div>
                <p class="mb-1">${log.description || ''}</p>
                <small class="text-muted">Oleh: ${log.user.name}</small>
            </div>`;
        });
        html += '</div>';
        $('#historyContent').html(html);
    });
}

// View Notes
function viewNotes(id) {
    // Implementation for viewing notes
    alert('View notes for attendance ID: ' + id);
}
</script>
@endpush