<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModuleLimit extends Model
{
    use HasUuids;

    protected $fillable = [
        'module_uuid',
        'free_limit'
    ];

    protected $casts = [
        'free_limit' => 'array'
    ];

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class, 'module_uuid', 'uuid');
    }
}