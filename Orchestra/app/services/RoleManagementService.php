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

    public function createRole(array $data, Enterprise $enterprise): Role
    {
        $this->checkRoleLimit($enterprise);

        return DB::transaction(function () use ($data, $enterprise) {
            return Role::create([
                'name' => $data['name'],
                'color_hex' => $data['color_hex'],
                'enterprise_uuid' => $enterprise->uuid,
                'authority' => $this->authorityService->process($data['authority'] ?? [])
            ]);
        });
    }

    private function checkRoleLimit(Enterprise $enterprise): void
    {
        $currentRolesCount = $enterprise->roles()->count();
        $moduleLimit = $enterprise->modules()
            ->where('key', 'roles')
            ->first()
            ->limits
            ?->free_limit['maxRoles'] ?? null;

        if ($moduleLimit && $currentRolesCount >= $moduleLimit) {
            throw new RoleLimitExceededException($currentRolesCount, $moduleLimit);
        }
    }
}