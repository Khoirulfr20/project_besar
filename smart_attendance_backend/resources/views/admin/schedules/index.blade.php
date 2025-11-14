{{-- ============================================ --}}
{{-- File: resources/views/admin/schedules/index.blade.php --}}
{{-- ============================================ --}}
@extends('layouts.app')
@section('title', 'Kelola Jadwal')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Kelola Jadwal</h1>
    <a href="{{ route('admin.schedules.create') }}" class="btn btn-primary">
        <i class="fas fa-plus"></i> Tambah Jadwal
    </a>
</div>

<div class="card">
    <div class="card-body">

        <table id="schedulesTable" class="table table-striped table-hover">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Judul</th>
                    <th>Waktu</th>
                    <th>Lokasi</th>
                    <th>Tipe</th>
                    <th>Status</th>
                    <th>Peserta</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>

            <tbody>
                @foreach($schedules as $schedule)
                <tr>
                    <td>{{ $schedule->date->format('d/m/Y') }}</td>
                    <td class="fw-semibold">{{ $schedule->title }}</td>
                    <td>{{ $schedule->start_time }} - {{ $schedule->end_time }}</td>
                    <td>{{ $schedule->location ?? '-' }}</td>

                    <td>
                        <span class="badge bg-info">{{ ucfirst($schedule->type) }}</span>
                    </td>

                    <td>
                        @if($schedule->status == 'scheduled')
                            <span class="badge bg-primary">Terjadwal</span>
                        @elseif($schedule->status == 'ongoing')
                            <span class="badge bg-success">Berlangsung</span>
                        @elseif($schedule->status == 'completed')
                            <span class="badge bg-secondary">Selesai</span>
                        @else
                            <span class="badge bg-danger">Dibatalkan</span>
                        @endif
                    </td>

                    <td>{{ $schedule->participants->count() }} orang</td>

                    <td class="text-center">
                        <a href="{{ route('admin.schedules.edit', $schedule->id) }}" 
                           class="btn btn-sm btn-warning">
                            <i class="fas fa-edit"></i>
                        </a>

                        <form action="{{ route('admin.schedules.destroy', $schedule->id) }}" 
                              method="POST" class="d-inline" 
                              onsubmit="return confirm('Yakin hapus jadwal ini?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </td>

                </tr>
                @endforeach
            </tbody>

        </table>

    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('#schedulesTable').DataTable({
        language: { url: '//cdn.datatables.net/plug-ins/1.13.5/i18n/id.json' },
        order: [[0, 'desc']]
    });
});
</script>
@endpush
