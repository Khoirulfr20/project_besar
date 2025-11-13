<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens; // ✅ Tambahkan ini

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes; // ✅ Tambahkan HasApiTokens

    protected $fillable = [
        'employee_id',
        'name',
        'email',
        'password',
        'role',
        'phone',
        'position',
        'department',
        'photo',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
        'password' => 'hashed',
    ];

    // ✅ Hapus fungsi-fungsi JWT (tidak dibutuhkan oleh Sanctum)
    // public function getJWTIdentifier() { ... }
    // public function getJWTCustomClaims() { ... }

    // Relationships
    public function faceData()
    {
        return $this->hasMany(FaceData::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function scheduleParticipants()
    {
        return $this->hasMany(ScheduleParticipant::class);
    }

    public function schedules()
    {
        return $this->belongsToMany(Schedule::class, 'schedule_participants')
                    ->withPivot('participant_type', 'is_notified')
                    ->withTimestamps();
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function createdSchedules()
    {
        return $this->hasMany(Schedule::class, 'created_by');
    }

    public function generatedReports()
    {
        return $this->hasMany(Report::class, 'generated_by');
    }

    public function approvedAttendances()
    {
        return $this->hasMany(Attendance::class, 'approved_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRole($query, $role)
    {
        return $query->where('role', $role);
    }

    public function scopeByDepartment($query, $department)
    {
        return $query->where('department', $department);
    }

    // Helper Methods
    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function isPimpinan()
    {
        return $this->role === 'pimpinan';
    }

    public function isAnggota()
    {
        return $this->role === 'anggota';
    }

    public function hasRole($role)
    {
        return $this->role === $role;
    }

    public function hasFaceData()
    {
        return $this->faceData()->where('is_active', true)->exists();
    }

    public function getFullPhotoUrlAttribute()
    {
        return $this->photo ? url('storage/' . $this->photo) : null;
    }
}
