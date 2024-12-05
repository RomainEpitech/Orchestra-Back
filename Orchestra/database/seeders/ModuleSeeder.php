<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\ModuleLimit;
use Illuminate\Database\Seeder;

class ModuleSeeder extends Seeder
{
    public function run(): void
    {
        $modules = [
            [
                'name' => 'Personnel',
                'description' => 'Gérez vos employés, leurs absences et leurs informations',
                'key' => 'personnel',
                'is_core' => true,
                'purchase_price' => 149.99,
                'limits' => [
                    'maxUsers' => 30,
                ]
            ],
            [
                'name' => 'Roles & Permissions',
                'description' => 'Gérez les rôles et permissions de votre entreprise',
                'key' => 'roles',
                'is_core' => true,
                'purchase_price' => 99.99,
                'limits' => [
                    'maxRoles' => 5,
                    'availableColors' => ['#FF0000', '#00FF00', '#0000FF']
                ]
            ],
            // Autres modules...
        ];

        foreach ($modules as $moduleData) {
            $limits = $moduleData['limits'];
            unset($moduleData['limits']);

            $module = Module::updateOrCreate(
                ['key' => $moduleData['key']],
                $moduleData
            );

            ModuleLimit::updateOrCreate(
                ['module_uuid' => $module->uuid],
                ['free_limit' => $limits]
            );
        }
    }
}