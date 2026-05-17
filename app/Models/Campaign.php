<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Campaign extends Model
{
    protected $fillable = [
        'user_id', 'title', 'slug', 'description', 'content',
        'image', 'category', 'target_amount', 'collected_amount',
        'donor_count', 'deadline', 'status', 'is_featured',
    ];

    protected $casts = [
        'target_amount'    => 'float',
        'collected_amount' => 'float',
        'deadline'         => 'date',
        'is_featured'      => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($campaign) {
            if (empty($campaign->slug)) {
                $campaign->slug = Str::slug($campaign->title) . '-' . Str::random(5);
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function donations()
    {
        return $this->hasMany(Donation::class);
    }

    public function getProgressPercentageAttribute(): float
    {
        if ($this->target_amount <= 0) return 0;
        return min(100, round(($this->collected_amount / $this->target_amount) * 100, 2));
    }

    public function getDaysLeftAttribute(): ?int
    {
        if (!$this->deadline) return null;
        return max(0, now()->diffInDays($this->deadline, false));
    }
}
