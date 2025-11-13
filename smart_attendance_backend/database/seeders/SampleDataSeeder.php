<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Schedule;
use App\Models\Attendance;
use Carbon\Carbon;

class SampleDataSeeder extends Seeder
{
    /**
     * Seed sample data for testing
     */
    public function run(): void
    {
        // Create sample schedules
        $this->createSampleSchedules();
        
        // Create sample attendances
        $this->createSampleAttendances();
    }

    private function createSampleSchedules()
    {
        // Schedule for today
        $schedule1 = Schedule::create([
            'title' => 'Meeting Tim Development',
            'description' => 'Diskusi progress project Smart Attendance',
            'date' => Carbon::today(),
            'start_time' => '09:00',
            'end_time' => '11:00',
            'location' => 'Ruang Meeting A',
            'type' => 'meeting',
            'status' => 'scheduled',
            'created_by' => 1, // Admin
        ]);
        
        // Add participants
        $schedule1->participants()->attach([2, 3, 4, 5]); // Pimpinan & Anggota

        // Schedule for tomorrow
        $schedule2 = Schedule::create([
            'title' => 'Training Face Recognition',
            'description' => 'Pelatihan penggunaan sistem face recognition',
            'date' => Carbon::tomorrow(),
            'start_time' => '13:00',
            'end_time' => '15:00',
            'location' => 'Lab Komputer',
            'type' => 'training',
            'status' => 'scheduled',
            'created_by' => 1,
        ]);
        
        $schedule2->participants()->attach([3, 4, 5]);

        // Past schedule
        $schedule3 = Schedule::create([
            'title' => 'Company Gathering',
            'description' => 'Acara gathering perusahaan',
            'date' => Carbon::yesterday(),
            'start_time' => '10:00',
            'end_time' => '14:00',
            'location' => 'Gedung Serbaguna',
            'type' => 'event',
            'status' => 'completed',
            'created_by' => 1,
        ]);
        
        $schedule3->participants()->attach([2, 3, 4, 5]);
    }

    private function createSampleAttendances()
    {
        // Today's attendance - Hadir
        Attendance::create([
            'user_id' => 3,
            'schedule_id' => 1,
            'date' => Carbon::today(),
            'check_in_time' => '08:50',
            'check_in_confidence' => 0.92,
            'check_out_time' => '17:15',
            'check_out_confidence' => 0.89,
            'status' => 'present',
            'work_duration' => 505, // 8 jam 25 menit
        ]);

        // Today's attendance - Terlambat
        Attendance::create([
            'user_id' => 4,
            'schedule_id' => 1,
            'date' => Carbon::today(),
            'check_in_time' => '08:25',
            'check_in_confidence' => 0.88,
            'check_out_time' => '17:10',
            'check_out_confidence' => 0.91,
            'status' => 'late',
            'work_duration' => 525, // 8 jam 45 menit
        ]);

        // Yesterday's attendance
        Attendance::create([
            'user_id' => 3,
            'date' => Carbon::yesterday(),
            'check_in_time' => '08:00',
            'check_in_confidence' => 0.94,
            'check_out_time' => '17:00',
            'check_out_confidence' => 0.90,
            'status' => 'present',
            'work_duration' => 540, // 9 jam
        ]);

        Attendance::create([
            'user_id' => 4,
            'date' => Carbon::yesterday(),
            'check_in_time' => '08:05',
            'check_in_confidence' => 0.87,
            'check_out_time' => '17:05',
            'check_out_confidence' => 0.93,
            'status' => 'present',
            'work_duration' => 540,
        ]);

        Attendance::create([
            'user_id' => 5,
            'date' => Carbon::yesterday(),
            'status' => 'absent',
        ]);

        // Last week's data
        for ($i = 2; $i <= 6; $i++) {
            $date = Carbon::today()->subDays($i);
            
            Attendance::create([
                'user_id' => 3,
                'date' => $date,
                'check_in_time' => '08:00',
                'check_in_confidence' => rand(80, 95) / 100,
                'check_out_time' => '17:00',
                'check_out_confidence' => rand(80, 95) / 100,
                'status' => 'present',
                'work_duration' => 540,
            ]);

            Attendance::create([
                'user_id' => 4,
                'date' => $date,
                'check_in_time' => rand(8, 9) . ':' . str_pad(rand(0, 30), 2, '0', STR_PAD_LEFT),
                'check_in_confidence' => rand(80, 95) / 100,
                'check_out_time' => '17:00',
                'check_out_confidence' => rand(80, 95) / 100,
                'status' => rand(0, 10) > 7 ? 'late' : 'present',
                'work_duration' => rand(480, 540),
            ]);
        }
    }
}