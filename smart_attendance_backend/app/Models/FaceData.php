<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FaceData extends Model
{
    use HasFactory;

    protected $table = 'face_data';

    protected $guarded = [];

    protected $fillable = [
        'user_id',
        'face_encoding',
        'face_photo',
        'registration_photo',
        'face_sample_number',
        'quality_score',
        'is_primary',
        'face_registered_at',
    ];

    protected $casts = [
        'quality_score' => 'float',
        'is_primary' => 'boolean',
        'is_active' => 'boolean',
        'face_sample_number' => 'integer',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    // Helper Methods
    public function getFullPhotoUrlAttribute()
    {
        return $this->face_photo ? url('storage/' . $this->face_photo) : null;
    }

    public function getFaceEncodingArrayAttribute()
    {
        return json_decode($this->face_encoding, true);
    }

    public function setFaceEncodingArrayAttribute($value)
    {
        $this->attributes['face_encoding'] = json_encode($value);
    }
}