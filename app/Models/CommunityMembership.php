<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommunityMembership extends Model
{
    protected $table = 'community_memberships';

    public const DEFAULT_NOTIFICATION_PREFERENCES = [
        'posts' => true,
        'events' => true,
        'photos' => true,
        'memberships' => true,
    ];

    protected $fillable = [
        'community_id',
        'user_id',
        'role',   // 'owner' | 'admin' | 'moderator' | 'member'
        'status', // 'active' | 'pending' | 'banned'
        'notification_preferences',
    ];

    protected $casts = [
        'notification_preferences' => 'array',
    ];

    public function community()
    {
        return $this->belongsTo(Community::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
