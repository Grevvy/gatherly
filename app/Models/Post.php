<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Post extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'content',
        'status',
        'published_at',
        'user_id',
        'community_id',
        'image_path',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function community(): BelongsTo
    {
        return $this->belongsTo(Community::class);
    }

    // Helper methods for post status
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    // Scopes for easier querying
    public function scopeDrafts($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    // Auto-approve posts from moderators and owners
    protected static function booted()
    {
        static::creating(function ($post) {
            if ($post->status === 'pending') {
                $membership = CommunityMembership::where('community_id', $post->community_id)
                    ->where('user_id', $post->user_id)
                    ->first();

                if ($membership && in_array($membership->role, ['owner', 'admin', 'moderator'])) {
                    $post->status = 'published';
                    $post->published_at = now();
                }
            }
        });
    }
}