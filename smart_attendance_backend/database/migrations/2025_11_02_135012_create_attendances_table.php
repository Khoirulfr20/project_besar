<?php

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
            
            // Check-in
            $table->time('check_in_time')->nullable();
            $table->string('check_in_photo')->nullable()->comment('Foto saat check-in');
            $table->float('check_in_confidence')->nullable()->comment('Confidence score face recognition');
            $table->string('check_in_location')->nullable()->comment('Lokasi GPS check-in');
            $table->string('check_in_device')->nullable()->comment('Device info');
            
            // Check-out
            $table->time('check_out_time')->nullable();
            $table->string('check_out_photo')->nullable()->comment('Foto saat check-out');
            $table->float('check_out_confidence')->nullable()->comment('Confidence score face recognition');
            $table->string('check_out_location')->nullable()->comment('Lokasi GPS check-out');
            $table->string('check_out_device')->nullable()->comment('Device info');
            
            // Status & Additional Info
            $table->enum('status', ['present', 'late', 'absent', 'excused', 'leave'])->default('present');
            $table->integer('work_duration')->nullable()->comment('Durasi kerja dalam menit');
            $table->text('notes')->nullable()->comment('Catatan tambahan');
            $table->foreignId('approved_by')->nullable()->constrained('users')->comment('Disetujui oleh');
            $table->timestamp('approved_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('user_id');
            $table->index('schedule_id');
            $table->index('date');
            $table->index('status');
            $table->index(['user_id', 'date']);
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