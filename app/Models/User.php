<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'bio',
        'phone',
        'location',
        'website',
        'avatar',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'interests' => 'array',
    ];

    // Helper: whether the user is a site admin (global)
    public function isSiteAdmin(): bool
    {
        return (bool) ($this->is_site_admin ?? false);
    }

    /**
     * Get all posts created by the user
     */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    /**
     * Get user memberships in communities
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(\App\Models\CommunityMembership::class, 'user_id');
    }

    /**
     * Get all likes created by the user
     */
    public function likes(): HasMany
    {
        return $this->hasMany(Like::class);
    }

    /**
     * Get all comments created by the user
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * Get all photos uploaded by the user
     */
    public function photos(): HasMany
    {
        return $this->hasMany(Photo::class);
    }

    /**
     * Get the URL for the user's avatar
     */
    public function getAvatarUrlAttribute(): ?string
    {
        if (!$this->avatar) {
            return null;
        }
        
        // Use the public disk configuration URL
        return config('filesystems.disks.public.url') . '/' . $this->avatar;
    }

    /**
     * Get the URL for the user's banner
     */
    public function getBannerUrlAttribute(): ?string
    {
        if (!$this->banner) {
            return null;
        }
        
        // Use the public disk configuration URL
        return config('filesystems.disks.public.url') . '/' . $this->banner;
    }
}