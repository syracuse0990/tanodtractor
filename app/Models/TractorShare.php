<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TractorShare extends Model
{
    protected $fillable = [
        'token',
        'imei',
        'device_name',
        'created_by',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    /**
     * Check if the share link has expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Scope: only active (non-expired) shares.
     */
    public function scopeActive($query)
    {
        return $query->where('expires_at', '>', now());
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
