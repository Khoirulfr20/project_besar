<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Menambahkan kolom GPS location tracking untuk attendance
     * - Latitude & Longitude untuk check-in dan check-out
     * - Distance (jarak dari kantor dalam meter)
     * - Admin entry flag untuk membedakan input manual vs mobile app
     */
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            // ========================================
            // CHECK-IN GPS DATA
            // ========================================
            $table->decimal('check_in_latitude', 10, 8)
                ->nullable()
                ->after('check_in_device')
                ->comment('Latitude lokasi saat check-in');
            
            $table->decimal('check_in_longitude', 11, 8)
                ->nullable()
                ->after('check_in_latitude')
                ->comment('Longitude lokasi saat check-in');
            
            $table->integer('check_in_distance')
                ->nullable()
                ->after('check_in_longitude')
                ->comment('Jarak dari kantor saat check-in (meter)');

            // ========================================
            // CHECK-OUT GPS DATA
            // ========================================
            $table->decimal('check_out_latitude', 10, 8)
                ->nullable()
                ->after('check_out_device')
                ->comment('Latitude lokasi saat check-out');
            
            $table->decimal('check_out_longitude', 11, 8)
                ->nullable()
                ->after('check_out_latitude')
                ->comment('Longitude lokasi saat check-out');
            
            $table->integer('check_out_distance')
                ->nullable()
                ->after('check_out_longitude')
                ->comment('Jarak dari kantor saat check-out (meter)');

            // ========================================
            // TRACKING & FLAGS
            // ========================================
            $table->boolean('admin_entry')
                ->default(false)
                ->after('has_anomaly')
                ->comment('True jika input dari admin panel (tidak perlu GPS)');

            // ========================================
            // INDEXES untuk performa query
            // ========================================
            $table->index('check_in_distance', 'idx_check_in_distance');
            $table->index('check_out_distance', 'idx_check_out_distance');
            $table->index('admin_entry', 'idx_admin_entry');
        });
    }

    /**
     * Reverse the migrations.
     * 
     * Menghapus kolom GPS yang ditambahkan
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            // Drop indexes dulu
            $table->dropIndex('idx_check_in_distance');
            $table->dropIndex('idx_check_out_distance');
            $table->dropIndex('idx_admin_entry');

            // Drop columns
            $table->dropColumn([
                'check_in_latitude',
                'check_in_longitude',
                'check_in_distance',
                'check_out_latitude',
                'check_out_longitude',
                'check_out_distance',
                'admin_entry',
            ]);
        });
    }
};