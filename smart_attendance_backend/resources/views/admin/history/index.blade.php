{{-- ============================================ --}}
{{-- File: resources/views/admin/history/index.blade.php --}}
{{-- ============================================ --}}
@extends('layouts.app')
@section('title', 'Histori Kehadiran')

@section('content')
<div class="pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Histori Kehadiran</h1>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Tanggal Mulai</label>
                <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Tanggal Akhir</label>
                <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">User</label>
                <select name="user_id" class="form-select">
                    <option value="">Semua</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                            {{ $user->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn btn-primary d-block w-100">Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Nama</th>
                    <th>Check-In</th>
                    <th>Check-Out</th>
                    <th>Status</th>
                    <th>Foto</th>
                </tr>
            </thead>
            <tbody>
                @foreach($attendances as $att)
                <tr>
                    <td>{{ $att->date->format('d/m/Y') }}</td>
                    <td>{{ $att->user->name }}</td>
                    <td>{{ $att->check_in_time ?? '-' }}</td>
                    <td>{{ $att->check_out_time ?? '-' }}</td>
                    <td>
                        @if($att->status == 'present')
                            <span class="badge bg-success">Hadir</span>
                        @elseif($att->status == 'late')
                            <span class="badge bg-warning">Terlambat</span>
                        @else
                            <span class="badge bg-secondary">{{ ucfirst($att->status) }}</span>
                        @endif
                    </td>
                    <td>
                        @if($att->check_in_photo)
                            <a href="{{ Storage::url($att->check_in_photo) }}" target="_blank" class="btn btn-sm btn-info">
                                <i class="fas fa-image"></i> In
                            </a>
                        @endif
                        @if($att->check_out_photo)
                            <a href="{{ Storage::url($att->check_out_photo) }}" target="_blank" class="btn btn-sm btn-info">
                                <i class="fas fa-image"></i> Out
                            </a>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        {{ $attendances->links() }}
    </div>
</div>
@endsection