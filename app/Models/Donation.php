<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Donation extends Model
{
    protected $fillable = [
        'user_id', 'campaign_id', 'order_id', 'amount',
        'donor_name', 'donor_email', 'donor_phone', 'message',
        'is_anonymous', 'status', 'payment_type',
        'payment_url', 'snap_token', 'midtrans_response', 'paid_at',
    ];

    protected $casts = [
        'amount'             => 'float',
        'is_anonymous'       => 'boolean',
        'midtrans_response'  => 'array',
        'paid_at'            => 'datetime',
    ];

    public static function generateOrderId(): string
    {
        return 'DON-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->is_anonymous ? 'Donatur Anonim' : $this->donor_name;
    }
}
