<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Schedule extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'date',
        'start_time',
        'end_time',
        'location',
        'type',
        'status',
        'created_by',
        'is_active',
    ];

    protected $casts = [
        'date' => 'date',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function participants()
    {
        return $this->belongsToMany(User::class, 'schedule_participants')
                    ->withPivot('participant_type', 'is_notified')
                    ->withTimestamps();
    }

    public function scheduleParticipants()
    {
        return $this->hasMany(ScheduleParticipant::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('date', '>=', now()->toDateString())
                    ->where('status', 'scheduled')
                    ->orderBy('date')
                    ->orderBy('start_time');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('date', now()->toDateString());
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->whereHas('participants', function($q) use ($userId) {
            $q->where('user_id', $userId);
        });
    }

    // Helper Methods
    public function isOngoing()
    {
        $now = now();
        $scheduleDate = Carbon::parse($this->date);
        $startDateTime = $scheduleDate->copy()->setTimeFromTimeString($this->start_time);
        $endDateTime = $scheduleDate->copy()->setTimeFromTimeString($this->end_time);

        return $now->between($startDateTime, $endDateTime);
    }

    public function isPast()
    {
        $scheduleDate = Carbon::parse($this->date);
        $endDateTime = $scheduleDate->copy()->setTimeFromTimeString($this->end_time);
        
        return now()->greaterThan($endDateTime);
    }

    public function getDurationInMinutes()
    {
        $start = Carbon::parse($this->start_time);
        $end = Carbon::parse($this->end_time);
        
        return $start->diffInMinutes($end);
    }

    public function getAttendanceRate()
    {
        $totalParticipants = $this->participants()->count();
        if ($totalParticipants === 0) return 0;

        $attendedCount = $this->attendances()
                              ->whereIn('status', ['present', 'late'])
                              ->count();

        return ($attendedCount / $totalParticipants) * 100;
    }

    public function addParticipant($userId, $type = 'required')
    {
        return $this->participants()->attach($userId, [
            'participant_type' => $type,
            'is_notified' => false,
        ]);
    }

    public function removeParticipant($userId)
    {
        return $this->participants()->detach($userId);
    }
}