<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setting;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // General Settings
            [
                'key' => 'app_name',
                'value' => 'Smart Attendance System',
                'type' => 'string',
                'group' => 'general',
                'description' => 'Nama aplikasi',
                'is_public' => true,
            ],
            [
                'key' => 'company_name',
                'value' => 'PT. Smart Company',
                'type' => 'string',
                'group' => 'general',
                'description' => 'Nama perusahaan',
                'is_public' => true,
            ],
            
            // Attendance Settings
            [
                'key' => 'work_start_time',
                'value' => '08:00',
                'type' => 'string',
                'group' => 'attendance',
                'description' => 'Jam masuk kerja',
                'is_public' => true,
            ],
            [
                'key' => 'work_end_time',
                'value' => '17:00',
                'type' => 'string',
                'group' => 'attendance',
                'description' => 'Jam pulang kerja',
                'is_public' => true,
            ],
            [
                'key' => 'late_tolerance_minutes',
                'value' => '15',
                'type' => 'integer',
                'group' => 'attendance',
                'description' => 'Toleransi keterlambatan (menit)',
                'is_public' => false,
            ],
            [
                'key' => 'allow_early_checkin_minutes',
                'value' => '30',
                'type' => 'integer',
                'group' => 'attendance',
                'description' => 'Boleh check-in lebih awal (menit)',
                'is_public' => false,
            ],
            
            // Face Recognition Settings
            [
                'key' => 'face_confidence_threshold',
                'value' => '0.75',
                'type' => 'string',
                'group' => 'face_recognition',
                'description' => 'Threshold confidence untuk face recognition (0-1)',
                'is_public' => false,
            ],
            [
                'key' => 'face_samples_required',
                'value' => '3',
                'type' => 'integer',
                'group' => 'face_recognition',
                'description' => 'Jumlah sample wajah yang diperlukan',
                'is_public' => false,
            ],
            
            // Notification Settings
            [
                'key' => 'enable_notifications',
                'value' => 'true',
                'type' => 'boolean',
                'group' => 'notification',
                'description' => 'Aktifkan notifikasi',
                'is_public' => false,
            ],
            [
                'key' => 'notify_before_schedule_minutes',
                'value' => '30',
                'type' => 'integer',
                'group' => 'notification',
                'description' => 'Notifikasi sebelum jadwal (menit)',
                'is_public' => false,
            ],
        ];

        foreach ($settings as $setting) {
            Setting::create($setting);
        }
    }
}