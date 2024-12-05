<?php

namespace App\Services;

use App\Models\Enterprise;
use App\Models\Module;

class ModuleAssignmentService
{
    /**
     * Module attribution on register handler
     */
    public static function assignModules(Enterprise $enterprise): void
    {
        $modules = Module::all();

        foreach ($modules as $module) {
            $enterprise->modules()->attach($module->uuid, [
                'uuid' => \Illuminate\Support\Str::uuid(),
                'is_activated' => $module->is_core
            ]);
        }
    }
}