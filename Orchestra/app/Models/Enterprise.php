<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Enterprise extends Model
{
    use HasUuids;
    use HasFactory;

    protected $primaryKey = 'uuid';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'key',
        'status',
    ];

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

    /**
     * Get the roles for the enterprise.
     */
    public function roles(): HasMany
    {
        return $this->hasMany(Role::class, 'enterprise_uuid', 'uuid');
    }

    /**
     * Get the modules for the enterprise.
     */
    public function modules(): BelongsToMany
    {
        return $this->belongsToMany(Module::class, 'enterprise_modules', 'enterprise_uuid', 'module_uuid')
                    ->withPivot('is_activated')
                    ->withTimestamps();
    }

    /**
     * Get the purchased modules for the enterprise.
     */
    public function purchasedModules(): BelongsToMany
    {
        return $this->belongsToMany(Module::class, 'enterprise_purchased_modules', 'enterprise_uuid', 'module_uuid')
                    ->withPivot('purchased_at')
                    ->withTimestamps();
    }

    /**
     * Get the subscriptions for the enterprise.
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(EnterpriseSubscription::class, 'enterprise_uuid', 'uuid');
    }

    /**
     * Check if enterprise has an active subscription.
     */
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