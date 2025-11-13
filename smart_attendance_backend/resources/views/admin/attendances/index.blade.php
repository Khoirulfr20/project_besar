{{-- ============================================ --}}
{{-- File: resources/views/admin/attendances/index.blade.php --}}
{{-- ============================================ --}}
@extends('layouts.app')
@section('title', 'Kelola Kehadiran')

@section('content')
<div class="d-flex justify-content-between pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Kelola Kehadiran</h1>
    <div>
        <input type="date" id="filterDate" class="form-control d-inline-block w-auto" value="{{ date('Y-m-d') }}">
    </div>
</div>

<div class="card">
    <div class="card-body">
        <table id="attendancesTable" class="table table-striped">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Nama</th>
                    <th>Check-In</th>
                    <th>Check-Out</th>
                    <th>Durasi</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($attendances as $att)
                <tr>
                    <td>{{ $att->date->format('d/m/Y') }}</td>
                    <td>{{ $att->user->name }}</td>
                    <td>
                        {{ $att->check_in_time ?? '-' }}
                        @if($att->check_in_photo)
                            <a href="{{ Storage::url($att->check_in_photo) }}" target="_blank">
                                <i class="fas fa-image"></i>
                            </a>
                        @endif
                    </td>
                    <td>
                        {{ $att->check_out_time ?? '-' }}
                        @if($att->check_out_photo)
                            <a href="{{ Storage::url($att->check_out_photo) }}" target="_blank">
                                <i class="fas fa-image"></i>
                            </a>
                        @endif
                    </td>
                    <td>{{ $att->work_duration ? floor($att->work_duration/60).'h '.($att->work_duration%60).'m' : '-' }}</td>
                    <td>
                        <select class="form-select form-select-sm status-select" data-id="{{ $att->id }}">
                            <option value="present" {{ $att->status == 'present' ? 'selected' : '' }}>Hadir</option>
                            <option value="late" {{ $att->status == 'late' ? 'selected' : '' }}>Terlambat</option>
                            <option value="absent" {{ $att->status == 'absent' ? 'selected' : '' }}>Tidak Hadir</option>
                            <option value="excused" {{ $att->status == 'excused' ? 'selected' : '' }}>Izin</option>
                            <option value="leave" {{ $att->status == 'leave' ? 'selected' : '' }}>Cuti</option>
                        </select>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-info" onclick="viewHistory({{ $att->id }})">
                            <i class="fas fa-history"></i>
                        </button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- Modal History -->
<div class="modal fade" id="historyModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Histori Kehadiran</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="historyContent">
                <div class="text-center"><div class="spinner-border"></div></div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('#attendancesTable').DataTable({
        language: { url: '//cdn.datatables.net/plug-ins/1.13.5/i18n/id.json' }
    });

    $('.status-select').change(function() {
        const id = $(this).data('id');
        const status = $(this).val();
        
        $.ajax({
            url: '/admin/attendances/' + id + '/status',
            method: 'PUT',
            data: { status, _token: '{{ csrf_token() }}' },
            success: function() {
                alert('Status berhasil diubah');
            }
        });
    });
});

function viewHistory(id) {
    $('#historyModal').modal('show');
    $('#historyContent').html('<div class="text-center"><div class="spinner-border"></div></div>');
    
    $.get('/admin/attendances/' + id + '/history', function(data) {
        let html = '<div class="list-group">';
        data.forEach(log => {
            html += `<div class="list-group-item">
                <div class="d-flex justify-content-between">
                    <strong>${log.action}</strong>
                    <small>${log.created_at}</small>
                </div>
                <p class="mb-0">${log.description || ''}</p>
                <small class="text-muted">Oleh: ${log.user.name}</small>
            </div>`;
        });
        html += '</div>';
        $('#historyContent').html(html);
    });
}
</script>
@endpush