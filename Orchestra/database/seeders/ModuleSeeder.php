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
                'name' => 'Entreprise',
                'description' => 'Gestion de vos modules Orchestra',
                'key' => 'entreprise',
                'is_core' => true,
                'purchase_price' => null,
                'limits' => ''
            ],
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
                'name' => 'Roles',
                'description' => 'Gérez les rôles et permissions de votre entreprise',
                'key' => 'roles',
                'is_core' => true,
                'purchase_price' => 99.99,
                'limits' => [
                    'maxRoles' => 5,
                    'availableColors' => ['#FF0000', '#00FF00', '#0000FF']
                ]
            ],
            [
                'name' => 'Events',
                'description' => 'Plannifiez vos evenements et réservations de salles.',
                'key' => 'events',
                'is_core' => false,
                'purchase_price' => 200,
                'limits' => ''
            ],
            [
                'name' => 'Absence',
                'description' => 'Gestion de demandes d\'absences avec envoie de mail pour confirmation',
                'key' => 'absence',
                'is_core' => false,
                'purchase_price' => 174.99,
                'limits' => ''
            ]
        ];

        foreach ($modules as $moduleData) {
            $limits = $moduleData['limits'];
            unset($moduleData['limits']);

            $module = Module::firstOrCreate(
                ['key' => $moduleData['key']],
                $moduleData
            );

            $moduleLimit = ModuleLimit::firstOrNew(['module_uuid' => $module->uuid]);
            $moduleLimit->free_limit = $limits;
            $moduleLimit->save();
        }
    }
}