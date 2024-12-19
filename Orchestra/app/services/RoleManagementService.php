<?php

namespace App\Services;

use App\Exceptions\RoleLimitExceededException;
use App\Models\Role;
use App\Models\Enterprise;
use Illuminate\Support\Facades\DB;

class RoleManagementService
{
    public function __construct(
        private AuthorityProcessingService $authorityService
    ) {}

    public function createRole(array $data, Enterprise $enterprise, bool $isModulePurchased): Role
    {
        if (!$isModulePurchased) {
            // Récupérer le module et ses limites
            $module = $enterprise->modules()
                ->where('key', 'roles')
                ->first();

            if ($module) {
                $currentCount = $enterprise->roles()->count();
                $maxRoles = $module->limits?->free_limit['maxRoles'] ?? null;

                if ($maxRoles && $currentCount >= $maxRoles) {
                    throw new RoleLimitExceededException($currentCount, $maxRoles);
                }
            }
        }

        return DB::transaction(function () use ($data, $enterprise) {
            return Role::create([
                'name' => $data['name'],
                'color_hex' => $data['color_hex'],
                'enterprise_uuid' => $enterprise->uuid,
                'authority' => $this->authorityService->process($data['authority'] ?? [])
            ]);
        });
    }
}