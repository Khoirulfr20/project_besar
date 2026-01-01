<?php
// database/migrations/2024_01_01_000002_create_face_data_table.php

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
        Schema::create('face_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // ✅ FIXED: LONGTEXT instead of JSON
            // LBPH embedding: 40,000 floats = ~250-350KB JSON string
            // JSON type akan auto-encode/decode, conflict dengan manual json_encode()
            $table->longText('face_encoding')
                ->comment('Face embedding LBPH (40,000 values as JSON string)');
            
            $table->string('face_photo')->nullable()
                ->comment('Path foto wajah (untuk file storage)');
            
            $table->text('registration_photo')->nullable()
                ->comment('Base64 foto registrasi (untuk backup)');
            
            // Metadata
            $table->integer('face_sample_number')->default(1)
                ->comment('Sample ke-berapa (untuk multiple samples)');
            
            $table->float('quality_score')->nullable()
                ->comment('Skor kualitas foto (0-1)');
            
            $table->boolean('is_primary')->default(false)
                ->comment('Apakah ini foto utama');
            
            // Tracking & Statistics
            $table->timestamp('face_registered_at')->nullable()
                ->comment('Waktu registrasi wajah');
            
            $table->timestamp('last_recognition_at')->nullable()
                ->comment('Terakhir dikenali');
            
            $table->integer('recognition_count')->default(0)
                ->comment('Jumlah berhasil dikenali');
            
            $table->float('avg_confidence', 5, 2)->nullable()
                ->comment('Rata-rata confidence score');
            
            $table->json('recognition_history')->nullable()
                ->comment('History 10 recognition terakhir');
            
            // Status
            $table->boolean('is_active')->default(true);
            
            $table->enum('registration_source', ['mobile', 'admin_panel', 'web'])
                ->default('mobile');
            
            $table->timestamps();
            
            // Indexes untuk performance
            $table->index('user_id');
            $table->index('is_active');
            $table->index('is_primary');
            $table->index('face_registered_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('face_data');
    }
};