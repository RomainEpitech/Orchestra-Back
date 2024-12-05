<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnterprisePurchasedModule extends Model
{
    use HasUuids;

    protected $fillable = [
        'enterprise_uuid',
        'module_uuid',
        'purchased_amount',
        'purchased_at'
    ];

    protected $casts = [
        'purchased_amount' => 'decimal:2',
        'purchased_at' => 'datetime'
    ];

    public function enterprise(): BelongsTo
    {
        return $this->belongsTo(Enterprise::class, 'enterprise_uuid', 'uuid');
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class, 'module_uuid', 'uuid');
    }
}