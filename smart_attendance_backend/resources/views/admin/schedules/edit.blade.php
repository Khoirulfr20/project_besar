@extends('layouts.app')

@section('content')
<div class="container-fluid px-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm border-0 mt-4">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-edit me-2"></i>Edit Schedule
                    </h4>
                </div>
                <div class="card-body">
                    <!-- Alert untuk error -->
                    @if ($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <strong>Oops!</strong> Ada beberapa masalah dengan input Anda:
                            <ul class="mb-0 mt-2">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <form action="{{ route('admin.schedules.update', $schedule->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <!-- Title -->
                            <div class="col-md-6 mb-3">
                                <label for="title" class="form-label">
                                    Judul <span class="text-danger">*</span>
                                </label>
                                <input 
                                    type="text" 
                                    class="form-control @error('title') is-invalid @enderror" 
                                    id="title" 
                                    name="title" 
                                    value="{{ old('title', $schedule->title) }}" 
                                    required
                                    placeholder="Contoh: Rapat Tim Marketing"
                                >
                                @error('title')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Date -->
                            <div class="col-md-6 mb-3">
                                <label for="date" class="form-label">
                                    Tanggal <span class="text-danger">*</span>
                                </label>
                                <input 
                                    type="date" 
                                    class="form-control @error('date') is-invalid @enderror" 
                                    id="date" 
                                    name="date" 
                                    value="{{ old('date', $schedule->date) }}" 
                                    required
                                >
                                @error('date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Start Time -->
                            <div class="col-md-6 mb-3">
                                <label for="start_time" class="form-label">
                                    Waktu Mulai <span class="text-danger">*</span>
                                </label>
                                <input 
                                    type="time" 
                                    class="form-control @error('start_time') is-invalid @enderror" 
                                    id="start_time" 
                                    name="start_time" 
                                    value="{{ old('start_time', $schedule->start_time) }}" 
                                    required
                                >
                                @error('start_time')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- End Time -->
                            <div class="col-md-6 mb-3">
                                <label for="end_time" class="form-label">
                                    Waktu Selesai <span class="text-danger">*</span>
                                </label>
                                <input 
                                    type="time" 
                                    class="form-control @error('end_time') is-invalid @enderror" 
                                    id="end_time" 
                                    name="end_time" 
                                    value="{{ old('end_time', $schedule->end_time) }}" 
                                    required
                                >
                                @error('end_time')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Location -->
                            <div class="col-md-6 mb-3">
                                <label for="location" class="form-label">
                                    Lokasi <span class="text-danger">*</span>
                                </label>
                                <input 
                                    type="text" 
                                    class="form-control @error('location') is-invalid @enderror" 
                                    id="location" 
                                    name="location" 
                                    value="{{ old('location', $schedule->location) }}" 
                                    required
                                    placeholder="Contoh: Ruang Meeting Lt. 3"
                                >
                                @error('location')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Type -->
                            <div class="col-md-6 mb-3">
                                <label for="type" class="form-label">
                                    Tipe <span class="text-danger">*</span>
                                </label>
                                <select 
                                    class="form-select @error('type') is-invalid @enderror" 
                                    id="type" 
                                    name="type"
                                    required
                                >
                                    <option value="">Pilih Tipe</option>
                                    <option value="meeting" {{ old('type', $schedule->type) == 'meeting' ? 'selected' : '' }}>Meeting</option>
                                    <option value="training" {{ old('type', $schedule->type) == 'training' ? 'selected' : '' }}>Training</option>
                                    <option value="event" {{ old('type', $schedule->type) == 'event' ? 'selected' : '' }}>Event</option>
                                    <option value="other" {{ old('type', $schedule->type) == 'other' ? 'selected' : '' }}>Lainnya</option>
                                </select>
                                @error('type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Description -->
                            <div class="col-12 mb-3">
                                <label for="description" class="form-label">
                                    Deskripsi
                                </label>
                                <textarea 
                                    class="form-control @error('description') is-invalid @enderror" 
                                    id="description" 
                                    name="description" 
                                    rows="4"
                                    placeholder="Tambahkan deskripsi schedule (opsional)"
                                >{{ old('description', $schedule->description) }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Status -->
                            <div class="col-md-6 mb-3">
                                <label for="status" class="form-label">
                                    Status <span class="text-danger">*</span>
                                </label>
                                <select 
                                    class="form-select @error('status') is-invalid @enderror" 
                                    id="status" 
                                    name="status"
                                    required
                                >
                                    <option value="scheduled" {{ old('status', $schedule->status) == 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                                    <option value="ongoing" {{ old('status', $schedule->status) == 'ongoing' ? 'selected' : '' }}>Ongoing</option>
                                    <option value="completed" {{ old('status', $schedule->status) == 'completed' ? 'selected' : '' }}>Completed</option>
                                    <option value="cancelled" {{ old('status', $schedule->status) == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                </select>
                                @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Buttons -->
                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <a href="{{ route('admin.schedules.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Kembali
                            </a>
                            <div>
                                <button type="reset" class="btn btn-outline-secondary me-2">
                                    <i class="fas fa-undo me-2"></i>Reset
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Update Schedule
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    .form-label {
        font-weight: 600;
        color: #495057;
    }
    
    .form-control:focus,
    .form-select:focus {
        border-color: #0d6efd;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
    }
    
    .card {
        border-radius: 10px;
    }
    
    .card-header {
        border-radius: 10px 10px 0 0 !important;
        padding: 1.25rem;
    }
</style>
@endpush

@push('scripts')
<script>
    // Validasi waktu mulai dan selesai
    document.getElementById('end_time').addEventListener('change', function() {
        const startTime = document.getElementById('start_time').value;
        const endTime = this.value;
        
        if (startTime && endTime && endTime <= startTime) {
            alert('Waktu selesai harus lebih besar dari waktu mulai!');
            this.value = '';
        }
    });

    // Set minimum date ke hari ini
    document.getElementById('date').setAttribute('min', new Date().toISOString().split('T')[0]);
</script>
@endpush
@endsection