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
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'published_at' => 'datetime',
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
}
