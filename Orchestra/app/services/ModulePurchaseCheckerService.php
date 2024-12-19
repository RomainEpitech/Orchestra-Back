<?php

namespace App\Services;

use App\Models\Enterprise;
use App\Models\Module;

class ModulePurchaseCheckerService
{
    public function isModulePurchased(Enterprise $enterprise, string $moduleKey): bool
    {
        return $enterprise->purchasedModules()
            ->where('modules.key', $moduleKey)
            ->exists();
    }
}