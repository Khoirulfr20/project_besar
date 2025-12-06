<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Attendance extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'schedule_id',
        'date',
        'check_in_time',
        'check_in_photo',
        'check_in_confidence',
        'check_in_device',
        'check_out_time',
        'check_out_photo',
        'check_out_confidence',
        'check_out_device',
        'status',
        'work_duration',
        'notes',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'date' => 'date',
        'check_in_confidence' => 'float',
        'check_out_confidence' => 'float',
        'work_duration' => 'integer',
        'approved_at' => 'datetime',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function schedule()
    {
        return $this->belongsTo(Schedule::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function logs()
    {
        return $this->hasMany(AttendanceLog::class);
    }

    // Scopes
    public function scopeToday($query)
    {
        return $query->whereDate('date', now()->toDateString());
    }

    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopePresent($query)
    {
        return $query->whereIn('status', ['present', 'late']);
    }

    public function scopeLate($query)
    {
        return $query->where('status', 'late');
    }

    public function scopeAbsent($query)
    {
        return $query->where('status', 'absent');
    }

    public function scopeApproved($query)
    {
        return $query->whereNotNull('approved_by');
    }

    public function scopePending($query)
    {
        return $query->whereNull('approved_by');
    }

    // Helper Methods
    public function checkIn($photoPath, $confidence, $location = null, $device = null)
    {
        $this->check_in_time = now()->format('H:i:s');
        $this->check_in_photo = $photoPath;
        $this->check_in_confidence = $confidence;
        $this->check_in_device = $device;
        
        // Determine status based on schedule
        $this->determineStatus();
        
        return $this->save();
    }

    public function checkOut($photoPath, $confidence, $location = null, $device = null)
    {
        $this->check_out_time = now()->format('H:i:s');
        $this->check_out_photo = $photoPath;
        $this->check_out_confidence = $confidence;
        $this->check_out_device = $device;
        
        // Calculate work duration
        $this->calculateWorkDuration();
        
        return $this->save();
    }

    protected function determineStatus()
    {
        if ($this->schedule) {
            $scheduleStartTime = Carbon::parse($this->schedule->start_time);
            $checkInTime = Carbon::parse($this->check_in_time);
            
            $lateToleranceMinutes = Setting::where('key', 'late_tolerance_minutes')->value('value') ?? 15;
            
            if ($checkInTime->greaterThan($scheduleStartTime->addMinutes($lateToleranceMinutes))) {
                $this->status = 'late';
            } else {
                $this->status = 'present';
            }
        } else {
            $this->status = 'present';
        }
    }

    protected function calculateWorkDuration()
    {
        if ($this->check_in_time && $this->check_out_time) {
            $checkIn = Carbon::parse($this->check_in_time);
            $checkOut = Carbon::parse($this->check_out_time);
            
            $this->work_duration = $checkIn->diffInMinutes($checkOut);
        }
    }

    public function approve($approverId, $notes = null)
    {
        $this->approved_by = $approverId;
        $this->approved_at = now();
        
        if ($notes) {
            $this->notes = $notes;
        }
        
        return $this->save();
    }

    public function isApproved()
    {
        return !is_null($this->approved_by);
    }

    public function isLate()
    {
        return $this->status === 'late';
    }

    public function getCheckInPhotoUrlAttribute()
    {
        return $this->check_in_photo ? url('storage/' . $this->check_in_photo) : null;
    }

    public function getCheckOutPhotoUrlAttribute()
    {
        return $this->check_out_photo ? url('storage/' . $this->check_out_photo) : null;
    }

    public function getWorkDurationFormattedAttribute()
    {
        if (!$this->work_duration) return null;
        
        $hours = floor($this->work_duration / 60);
        $minutes = $this->work_duration % 60;
        
        return sprintf('%d jam %d menit', $hours, $minutes);
    }
}