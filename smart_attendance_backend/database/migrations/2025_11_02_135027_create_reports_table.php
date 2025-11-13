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
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->string('title')->comment('Judul laporan');
            $table->enum('type', ['daily', 'weekly', 'monthly', 'custom'])->comment('Tipe laporan');
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('format', ['pdf', 'excel', 'csv'])->default('pdf');
            $table->string('file_path')->nullable()->comment('Path file hasil export');
            $table->integer('file_size')->nullable()->comment('Ukuran file dalam bytes');
            $table->json('filters')->nullable()->comment('Filter yang digunakan');
            $table->json('summary')->nullable()->comment('Ringkasan data laporan');
            $table->foreignId('generated_by')->constrained('users')->comment('Dibuat oleh');
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamps();
            
            $table->index('type');
            $table->index('generated_by');
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};