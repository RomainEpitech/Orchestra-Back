<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Module extends Model
{
    use HasUuids;

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'uuid';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The data type of the auto-incrementing ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'description',
        'key',
        'is_core',
        'purchase_price'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_core' => 'boolean',
        'purchase_price' => 'decimal:2'
    ];

    public function limits(): HasOne
    {
        return $this->hasOne(ModuleLimit::class, 'module_uuid', 'uuid');
    }

    public function enterprises(): BelongsToMany
    {
        return $this->belongsToMany(Enterprise::class, 'enterprise_modules', 'module_uuid', 'enterprise_uuid')
                    ->withPivot('is_activated')
                    ->withTimestamps();
    }

    public function purchasedBy(): BelongsToMany
    {
        return $this->belongsToMany(Enterprise::class, 'enterprise_purchased_modules', 'module_uuid', 'enterprise_uuid')
                    ->withPivot('purchased_amount', 'purchased_at')
                    ->withTimestamps();
    }
}