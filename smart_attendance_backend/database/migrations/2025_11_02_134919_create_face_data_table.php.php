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
        Schema::create('face_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('face_encoding')->comment('Face embedding data dari ML Kit');
            $table->string('face_photo')->comment('Path foto wajah');
            $table->integer('face_sample_number')->default(1)->comment('Sample ke-berapa (untuk multiple samples)');
            $table->float('quality_score')->nullable()->comment('Skor kualitas foto');
            $table->boolean('is_primary')->default(false)->comment('Apakah ini foto utama');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('user_id');
            $table->index('is_active');
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