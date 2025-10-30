<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Notifications\GatherlyResetPassword;

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
        'interests',
        'notifications_snoozed_until',
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
        'notifications_snoozed_until' => 'datetime',
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
     * Send the password reset notification with Gatherly branding.
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new GatherlyResetPassword($token));
    }
}
