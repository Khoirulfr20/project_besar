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
        Schema::create('attendance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->comment('User yang melakukan aksi');
            $table->enum('action', ['check_in', 'check_out', 'status_changed', 'approved', 'rejected', 'edited'])->comment('Jenis aksi');
            $table->text('description')->nullable()->comment('Deskripsi perubahan');
            $table->json('old_values')->nullable()->comment('Nilai sebelum perubahan');
            $table->json('new_values')->nullable()->comment('Nilai setelah perubahan');
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();
            
            $table->index('attendance_id');
            $table->index('user_id');
            $table->index('action');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_logs');
    }
};