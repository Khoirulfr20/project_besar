<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScheduleParticipant extends Model
{
    use HasFactory;

    protected $fillable = [
        'schedule_id',
        'user_id',
        'participant_type',
        'is_notified',
    ];

    protected $casts = [
        'is_notified' => 'boolean',
    ];

    // Relationships
    public function schedule()
    {
        return $this->belongsTo(Schedule::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeRequired($query)
    {
        return $query->where('participant_type', 'required');
    }

    public function scopeOptional($query)
    {
        return $query->where('participant_type', 'optional');
    }

    public function scopeNotified($query)
    {
        return $query->where('is_notified', true);
    }

    public function scopeNotNotified($query)
    {
        return $query->where('is_notified', false);
    }
}