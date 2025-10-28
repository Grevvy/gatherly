<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Community extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'visibility',   // 'public' | 'private'
        'join_policy',  // 'open' | 'request' | 'invite'
        'owner_id',
        'banner_image',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'owner_id' => 'integer',
    ];

    // Use slug in URLs: route model binding {community:slug}
    public function getRouteKeyName()
    {
        return 'slug';
    }

    // Relationships
    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function memberships()
    {
        return $this->hasMany(CommunityMembership::class);
    }

    public function members()
    {
        return $this->belongsToMany(User::class, 'community_memberships')
            ->withPivot(['role', 'status'])
            ->withTimestamps();
    }

    // Community has many events
    public function events()
    {
        return $this->hasMany(Event::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function channels(): HasMany
    {
        return $this->hasMany(Channel::class);
    }

    public function messageThreads(): HasMany
    {
        return $this->hasMany(MessageThread::class);
    }

    /**
     * Get all photos in this community
     */
    public function photos(): HasMany
    {
        return $this->hasMany(Photo::class);
    }

    // Helpers
    public function scopePublic($q)
    {
        return $q->where('visibility', 'public');
    }

    protected static function booted()
    {
        static::creating(function (Community $community) {
            if (empty($community->slug)) {
                $base = Str::slug($community->name);
                $slug = $base;
                $i = 1;
                while (static::where('slug', $slug)->withTrashed()->exists()) {
                    $slug = "{$base}-{$i}";
                    $i++;
                }
                $community->slug = $slug;
            }
        });
    }
}
