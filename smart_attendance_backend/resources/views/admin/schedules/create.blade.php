{{-- ============================================ --}}
{{-- File: resources/views/admin/schedules/create.blade.php --}}
{{-- ============================================ --}}
@extends('layouts.app')
@section('title', 'Tambah Jadwal')

@section('content')
<div class="d-flex justify-content-between pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Tambah Jadwal</h1>
    <a href="{{ route('admin.schedules.index') }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Kembali
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route('admin.schedules.store') }}" method="POST">
            @csrf
            
            <div class="mb-3">
                <label class="form-label">Judul Kegiatan *</label>
                <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" 
                       value="{{ old('title') }}" required>
                @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            
            <div class="mb-3">
                <label class="form-label">Deskripsi</label>
                <textarea name="description" class="form-control" rows="3">{{ old('description') }}</textarea>
            </div>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Tanggal *</label>
                        <input type="date" name="date" class="form-control @error('date') is-invalid @enderror" 
                               value="{{ old('date') }}" required>
                        @error('date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Jam Mulai *</label>
                        <input type="time" name="start_time" class="form-control @error('start_time') is-invalid @enderror" 
                               value="{{ old('start_time') }}" required>
                        @error('start_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Jam Selesai *</label>
                        <input type="time" name="end_time" class="form-control @error('end_time') is-invalid @enderror" 
                               value="{{ old('end_time') }}" required>
                        @error('end_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Lokasi</label>
                        <input type="text" name="location" class="form-control" value="{{ old('location') }}">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Tipe Kegiatan *</label>
                        <select name="type" class="form-select @error('type') is-invalid @enderror" required>
                            <option value="">Pilih Tipe</option>
                            <option value="meeting" {{ old('type') == 'meeting' ? 'selected' : '' }}>Meeting</option>
                            <option value="training" {{ old('type') == 'training' ? 'selected' : '' }}>Training</option>
                            <option value="event" {{ old('type') == 'event' ? 'selected' : '' }}>Event</option>
                            <option value="other" {{ old('type') == 'other' ? 'selected' : '' }}>Lainnya</option>
                        </select>
                        @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Peserta</label>
                <select name="participant_ids[]" class="form-select" multiple size="8">
                    @foreach($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->employee_id }})</option>
                    @endforeach
                </select>
                <small class="text-muted">Tahan Ctrl untuk memilih beberapa peserta</small>
            </div>
            
            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('admin.schedules.index') }}" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>
@endsection