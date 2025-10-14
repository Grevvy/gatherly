<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventAttendee extends Model
{
    use HasFactory;

    protected $table = 'event_attendees';

    protected $fillable = [
        'event_id',
        'user_id',
        'role',    // attendee, host, speaker
        'status',  // invited, accepted, declined, waitlist
        'checked_in',
    ];

    protected $casts = [
        'checked_in' => 'boolean',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
