<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'owner_id',
        'community_id',
        'location',
        'starts_at',
        'ends_at',
        'capacity',
        'visibility',
        'status',
        'boosted_at',
        'boosted_until',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'published_at' => 'datetime',
        'boosted_at' => 'datetime',
        'boosted_until' => 'datetime',
    ];

    // Owner (creator) of the event
    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    // Optional community the event belongs to
    public function community()
    {
        return $this->belongsTo(Community::class);
    }

    // EventAttendee pivot records
    public function attendees()
    {
        return $this->hasMany(EventAttendee::class);
    }

    // Users attending (convenience relationship)
    public function users()
    {
        return $this->belongsToMany(User::class, 'event_attendees')
            ->withPivot(['status', 'role', 'checked_in'])
            ->withTimestamps();
    }

    public function scopeOrdered($query)
    {
        return $query
            ->orderByRaw('CASE WHEN boosted_until IS NOT NULL AND boosted_until > NOW() THEN 0 ELSE 1 END')
            ->orderByDesc('boosted_until')
            ->orderBy('starts_at');
    }

    public function isBoosted(): bool
    {
        return $this->boosted_until !== null && $this->boosted_until->isFuture();
    }
}
