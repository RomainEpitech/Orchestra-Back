<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Enterprise extends Model
{
    use HasUuids;

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'uuid';

    /**
     * Indicates if the IDs are UUIDs.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The data type of the primary key ID.
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
        'key',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => 'boolean',
    ];

    /**
     * Get the users for the enterprise.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'enterprise_uuid', 'uuid');
    }

    public function modules(): BelongsToMany
    {
        return $this->belongsToMany(Module::class, 'enterprise_modules', 'enterprise_uuid', 'module_uuid')
                    ->withPivot('is_activated')
                    ->withTimestamps();
    }

    public function purchasedModules(): BelongsToMany
    {
        return $this->belongsToMany(Module::class, 'enterprise_purchased_modules', 'enterprise_uuid', 'module_uuid')
                    ->withPivot('purchased_amount', 'purchased_at')
                    ->withTimestamps();
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(EnterpriseSubscription::class, 'enterprise_uuid', 'uuid');
    }

    public function hasActiveSubscription(): bool
    {
        return $this->subscriptions()
                    ->where(function ($query) {
                        $query->whereNull('expires_at')
                            ->orWhere('expires_at', '>', now());
                    })
                    ->exists();
    }
}