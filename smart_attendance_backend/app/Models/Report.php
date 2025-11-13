<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'type',
        'start_date',
        'end_date',
        'format',
        'file_path',
        'file_size',
        'filters',
        'summary',
        'generated_by',
        'status',
        'error_message',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'filters' => 'array',
        'summary' => 'array',
        'file_size' => 'integer',
    ];

    // Relationships
    public function generator()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    // Scopes
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('generated_by', $userId);
    }

    // Helper Methods
    public function getDownloadUrlAttribute()
    {
        return $this->file_path ? url('storage/' . $this->file_path) : null;
    }

    public function getFileSizeFormattedAttribute()
    {
        if (!$this->file_size) return null;
        
        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->file_size;
        $unit = 0;
        
        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }
        
        return round($size, 2) . ' ' . $units[$unit];
    }

    public function markAsCompleted($filePath, $fileSize, $summary = null)
    {
        $this->file_path = $filePath;
        $this->file_size = $fileSize;
        $this->summary = $summary;
        $this->status = 'completed';
        
        return $this->save();
    }

    public function markAsFailed($errorMessage)
    {
        $this->status = 'failed';
        $this->error_message = $errorMessage;
        
        return $this->save();
    }
}