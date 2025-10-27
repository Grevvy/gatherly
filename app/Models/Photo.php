<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Photo extends Model
{
    protected $fillable = [
        'user_id',
        'community_id',
        'image_path',
        'caption',
    ];

    /**
     * Get the user who uploaded the photo.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the community this photo belongs to.
     */
    public function community(): BelongsTo
    {
        return $this->belongsTo(Community::class);
    }
}