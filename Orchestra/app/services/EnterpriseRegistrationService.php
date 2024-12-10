<?php

namespace App\Services;

use App\Models\Enterprise;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class EnterpriseRegistrationService
{
    protected ModuleAssignmentService $moduleService;

    public function __construct(ModuleAssignmentService $moduleService)
    {
        $this->moduleService = $moduleService;
    }

    public function register(array $data): array
    {
        try {
            $enterprise = Enterprise::create([
                'name' => $data['enterprise_name'],
                'key' => KeyGeneratorService::generateUniqueKey($data['enterprise_name']),
                'status' => true
            ]);

            $adminRole = Role::where('name', 'administrateur')
                ->where('is_default', true)
                ->firstOrFail();

            $admin = User::create([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role_uuid' => $adminRole->uuid,
                'enterprise_uuid' => $enterprise->uuid,
                'joined_at' => now(),
                'status' => true
            ]);

            $enterprise->update(['owner_uuid' => $admin->uuid]);
            $this->moduleService->assignModules($enterprise);

            $enterprise->load('modules');
            $admin->load('role');

            return [
                'enterprise' => $enterprise,
                'user' => $admin->makeHidden(['password']),
                'modules' => $enterprise->modules->map(function ($module) {
                    return [
                        'name' => $module->name,
                        'key' => $module->key,
                        'is_activated' => $module->pivot->is_activated
                    ];
                })
            ];

        } catch (\Exception $e) {
            Log::error('Error during enterprise registration: ' . $e->getMessage());
            throw $e;
        }
    }
}