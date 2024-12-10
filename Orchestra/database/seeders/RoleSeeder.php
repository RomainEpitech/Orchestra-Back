<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder {
    public function run(): void
    {
        $roles = [
            [
                'name' => 'administrateur',
                'enterprise_uuid' => null,
                'authority' => [
                    'personnel' => [
                        'read' => true,
                        'create' => true,
                        'edit' => true,
                        'delete' => true,
                        'manage_absences' => true
                    ],
                    'roles' => [
                        'read' => true,
                        'create' => true,
                        'edit' => true,
                        'delete' => true,
                        'assign_permissions' => true
                    ],
                    'events' => [
                        'read' => true,
                        'create' => true,
                        'edit' => true,
                        'delete' => true,
                        'manage_participants' => true,
                        'manage_rooms' => true
                    ],
                    'enterprise' => [
                        'read' => true,
                        'edit' => true,
                        'delete' => true,
                        'manage_modules' => true,
                        'manage_subscription' => true
                    ],
                    'absences' => [
                        'read' => true,
                        'create' => true,
                        'edit' => true,
                        'delete' => true,
                        'approve' => true
                    ],
                    'taches' => [
                        'read' => true,
                        'create' => true,
                        'edit' => true,
                        'delete' => true
                    ]
                ],
                'is_default' => 1,
                'color_hex' => '#67e8f9',
            ],
            [
                'name' => 'membre',
                'enterprise_uuid' => null,
                'authority' => [
                    'personnel' => [
                        'read' => false,
                        'create' => false,
                        'edit' => false,
                        'delete' => false,
                        'manage_absences' => false
                    ],
                    'roles' => [
                        'read' => false,
                        'create' => false,
                        'edit' => false,
                        'delete' => false,
                        'assign_permissions' => false
                    ],
                    'events' => [
                        'read' => true,
                        'create' => false,
                        'edit' => false,
                        'delete' => false,
                        'manage_participants' => false,
                        'manage_rooms' => false
                    ],
                    'enterprise' => [
                        'read' => false,
                        'edit' => false,
                        'delete' => false,
                        'manage_modules' => false,
                        'manage_subscription' => false
                    ],
                    'absences' => [
                        'read' => true,
                        'create' => true,
                        'edit' => false,
                        'delete' => false,
                        'approve' => false
                    ],
                    'taches' => [
                        'read' => true,
                        'create' => true,
                        'edit' => false,
                        'delete' => false
                    ]
                ],
                'is_default' => 1,
                'color_hex' => '#9ca3af'
            ]
        ];

        foreach ($roles as $roleData) {
            Role::create($roleData);
        }
    }
}