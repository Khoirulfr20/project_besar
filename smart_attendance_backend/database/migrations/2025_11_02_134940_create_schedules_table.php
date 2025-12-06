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
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->string('title')->comment('Judul kegiatan');
            $table->text('description')->nullable()->comment('Deskripsi kegiatan');
            $table->date('date')->comment('Tanggal kegiatan');
            $table->time('start_time')->comment('Jam mulai');
            $table->time('end_time')->comment('Jam selesai');
            $table->string('location')->nullable()->comment('Lokasi kegiatan');
            $table->enum('type', ['meeting', 'training', 'event', 'other'])->default('meeting');
            $table->enum('status', ['scheduled', 'ongoing', 'completed', 'cancelled'])->default('scheduled');
            $table->foreignId('created_by')->constrained('users')->comment('Dibuat oleh (admin)');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('date');
            $table->index('status');
            $table->index('created_by');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
