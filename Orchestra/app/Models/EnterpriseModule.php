<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class EnterpriseModule extends Pivot
{
    use HasUuids;

    protected $casts = [
        'is_activated' => 'boolean'
    ];
}