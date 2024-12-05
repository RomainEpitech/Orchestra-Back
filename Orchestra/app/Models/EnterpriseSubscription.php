<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnterpriseSubscription extends Model
{
    use HasUuids;

    protected $fillable = [
        'enterprise_uuid',
        'monthly_amount',
        'starts_at',
        'expires_at'
    ];

    protected $casts = [
        'monthly_amount' => 'decimal:2',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime'
    ];

    public function enterprise(): BelongsTo
    {
        return $this->belongsTo(Enterprise::class, 'enterprise_uuid', 'uuid');
    }

    public function isActive(): bool
    {
        return $this->expires_at === null || $this->expires_at->isFuture();
    }
}