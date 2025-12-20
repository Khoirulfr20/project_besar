@extends('layouts.app')
@section('title', 'Kelola Jadwal')

@section('content')

{{-- HEADER --}}
<div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
    <h4 class="fw-semibold m-0">Kelola Jadwal</h4>

    <a href="{{ route('admin.schedules.create') }}" class="btn btn-primary rounded-3 shadow-sm" onclick="loadingPopup()">
        <i class="fas fa-plus me-1"></i> Tambah Jadwal
    </a>
</div>

<div class="card shadow-sm border-0 rounded-4">
    <div class="card-body">

        <table id="schedulesTable" class="table modern-table table-hover align-middle">
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

                    {{-- Tanggal --}}
                    <td>{{ $schedule->date->format('d/m/Y') }}</td>

                    {{-- Judul + Show Detail --}}
                    <td class="fw-semibold">
                        <a href="#" class="text-primary text-decoration-none"
                        onclick="showDetail(
                            '{{ $schedule->title }}',
                            '{{ $schedule->description ?? '-' }}',
                            '{{ $schedule->date->format('d/m/Y') }}',
                            '{{ $schedule->start_time }} - {{ $schedule->end_time }}',
                            '{{ $schedule->location ?? '-' }}',
                            '{{ ucfirst($schedule->type) }}',
                            '{{ $schedule->participants->count() }} orang',
                            '{{ $schedule->auto_status }}'
                        )">
                        {{ $schedule->title }}
                        </a>
                    </td>

                    {{-- Waktu --}}
                    <td>{{ $schedule->start_time }} - {{ $schedule->end_time }}</td>

                    {{-- Lokasi --}}
                    <td>{{ $schedule->location ?? '-' }}</td>

                    {{-- Tipe --}}
                    <td>
                        <span class="badge bg-info">{{ ucfirst($schedule->type) }}</span>
                    </td>

                    {{-- Status --}}
                    <td>
                        @php
                            $autoStatus = $schedule->auto_status;
                        @endphp
                        
                        @switch($autoStatus)
                            @case('scheduled')  <span class="badge bg-primary">Terjadwal</span> @break
                            @case('ongoing')    <span class="badge bg-success">Berlangsung</span> @break
                            @case('completed')  <span class="badge bg-secondary">Selesai</span> @break
                            @default            <span class="badge bg-danger">Dibatalkan</span>
                        @endswitch
                    </td>

                    {{-- Jumlah Peserta --}}
                    <td>{{ $schedule->participants->count() }} orang</td>

                    {{-- Aksi --}}
                    <td class="text-center">
                        <a href="{{ route('admin.schedules.edit', $schedule->id) }}" 
                           class="btn btn-sm btn-warning rounded-3 me-1" onclick="loadingPopup()">
                            <i class="fas fa-edit"></i>
                        </a>

                        <button class="btn btn-sm btn-danger rounded-3"
                                onclick="deleteSchedule('{{ route('admin.schedules.destroy', $schedule->id) }}')">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>

                </tr>
                @endforeach
            </tbody>

        </table>

    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// ======================
// ======================
// DETAIL POPUP
// ======================
function showDetail(title, description, date, time, location, type, participants, status) {

    const badge = {
        scheduled : '<span class="badge bg-primary">Terjadwal</span>',
        ongoing   : '<span class="badge bg-success">Berlangsung</span>',
        completed : '<span class="badge bg-secondary">Selesai</span>',
        cancelled : '<span class="badge bg-danger">Dibatalkan</span>',
    }[status] ?? '-';

    Swal.fire({
        title: `<strong>${title}</strong>`,
        html: `
            <div class="text-start small pt-2">
                <p><strong>Deskripsi:</strong> ${description}</p>
                <hr class="my-2">
                <p><strong>Tanggal:</strong> ${date}</p>
                <p><strong>Waktu:</strong> ${time}</p>
                <p><strong>Lokasi:</strong> ${location}</p>
                <p><strong>Tipe:</strong> ${type}</p>
                <p><strong>Peserta:</strong> ${participants}</p>
                <p><strong>Status:</strong> ${badge}</p>
            </div>
        `,
        icon: "info",
        width: "36rem",
        confirmButtonText: "Tutup",
        confirmButtonColor: "#667eea",
    });
}

// ======================
// DELETE CONFIRMATION
// ======================
function deleteSchedule(url) {
    Swal.fire({
        title: "Hapus Jadwal?",
        text: "Data yang dihapus tidak dapat dikembalikan.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Hapus",
        cancelButtonText: "Batal",
        confirmButtonColor: "#d9534f",
        cancelButtonColor: "#6c757d",
    }).then(result => {
        if (result.isConfirmed) {
            let form = document.createElement("form");
            form.method = "POST";
            form.action = url;

            form.innerHTML = `
                @csrf
                <input type="hidden" name="_method" value="DELETE">
            `;

            document.body.appendChild(form);
            form.submit();
        }
    });
}

// ======================
// LOADING POPUP
// ======================
function loadingPopup() {
    Swal.fire({
        title: "Memuat...",
        text: "Harap tunggu sebentar",
        allowOutsideClick: false,
        showConfirmButton: false,
        didOpen: () => Swal.showLoading()
    });
}

// ======================
// DATATABLE
// ======================
$(document).ready(function () {
    $('#schedulesTable').DataTable({
        language: { url: "//cdn.datatables.net/plug-ins/1.13.5/i18n/id.json" },
        order: [[0, "desc"]]
    });
});
</script>

<style>
/* Modern table */
.modern-table thead {
    background: #f5f5ff;
}
.modern-table thead th {
    font-weight: 600;
    color: #444;
    border-bottom: 2px solid #e2e6f3;
}
.modern-table tbody tr:hover {
    background: #f3f4ff !important;
}

/* Rounded buttons */
.btn-sm {
    padding: 5px 10px;
    border-radius: 8px;
}
</style>

@endpush
