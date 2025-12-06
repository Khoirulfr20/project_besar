<?php
// database/migrations/2024_01_01_000005_create_attendances_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('schedule_id')->nullable()->constrained()->onDelete('set null');
            $table->date('date')->comment('Tanggal kehadiran');
            
            // Check-in Data
            $table->time('check_in_time')->nullable();
            $table->string('check_in_photo')->nullable()->comment('Path foto saat check-in');
            $table->text('check_in_photo_base64')->nullable()->comment('Base64 foto check-in (optional)');
            $table->float('check_in_confidence', 5, 2)->nullable()->comment('Confidence score face recognition');
            $table->string('check_in_device')->nullable()->comment('Device info');
            
            // Check-out Data
            $table->time('check_out_time')->nullable();
            $table->string('check_out_photo')->nullable()->comment('Path foto saat check-out');
            $table->text('check_out_photo_base64')->nullable()->comment('Base64 foto check-out (optional)');
            $table->float('check_out_confidence', 5, 2)->nullable()->comment('Confidence score face recognition');
            $table->string('check_out_device')->nullable()->comment('Device info');
            
            // Face Recognition Tracking
            $table->enum('check_in_method', ['manual', 'face_recognition', 'qr_code'])->default('face_recognition')->comment('Metode check-in');
            $table->enum('check_out_method', ['manual', 'face_recognition', 'qr_code'])->default('face_recognition')->comment('Metode check-out');
            $table->foreignId('check_in_recognized_user_id')->nullable()->constrained('users')->onDelete('set null')->comment('User ID yang dikenali saat check-in (untuk validasi)');
            $table->foreignId('check_out_recognized_user_id')->nullable()->constrained('users')->onDelete('set null')->comment('User ID yang dikenali saat check-out (untuk validasi)');
            $table->boolean('check_in_face_verified')->default(false)->comment('Apakah wajah check-in terverifikasi');
            $table->boolean('check_out_face_verified')->default(false)->comment('Apakah wajah check-out terverifikasi');
            
            // Status & Additional Info
            $table->enum('status', ['present', 'late', 'absent', 'excused', 'leave'])->default('present');
            $table->integer('work_duration')->nullable()->comment('Durasi kerja dalam menit');
            $table->text('notes')->nullable()->comment('Catatan tambahan');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null')->comment('Disetujui oleh');
            $table->timestamp('approved_at')->nullable();
            
            // Anomaly Detection
            $table->boolean('has_anomaly')->default(false)->comment('Flag jika ada keganjilan');
            $table->json('anomaly_details')->nullable()->comment('Detail anomali (misal: user berbeda, confidence rendah)');
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('user_id');
            $table->index('schedule_id');
            $table->index('date');
            $table->index('status');
            $table->index(['user_id', 'date']);
            $table->index('check_in_method');
            $table->index('check_out_method');
            $table->index('has_anomaly');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};