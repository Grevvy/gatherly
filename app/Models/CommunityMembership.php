<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommunityMembership extends Model
{
    protected $table = 'community_memberships';

    protected $fillable = [
        'community_id',
        'user_id',
        'role',   // 'owner' | 'admin' | 'moderator' | 'member'
        'status', // 'active' | 'pending' | 'banned'
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