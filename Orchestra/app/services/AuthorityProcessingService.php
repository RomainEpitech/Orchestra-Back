<?php

namespace App\Services;

use App\Config\Authorities;

class AuthorityProcessingService
{
    public function process(array $receivedAuthority): array
    {
        $defaultAuthority = Authorities::getDefault();
        
        foreach ($receivedAuthority as $module => $permissions) {
            if (isset($defaultAuthority[$module])) {
                foreach ($permissions as $permission => $value) {
                    if (isset($defaultAuthority[$module][$permission])) {
                        $defaultAuthority[$module][$permission] = (bool) $value;
                    }
                }
            }
        }

        return $defaultAuthority;
    }
}