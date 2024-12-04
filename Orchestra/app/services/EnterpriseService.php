<?php

namespace App\Services;

use App\Models\Enterprise;
use App\Services\KeyGeneratorService;

class EnterpriseService
{
    private KeyGeneratorService $keyGenerator;

    public function __construct(KeyGeneratorService $keyGenerator)
    {
        $this->keyGenerator = $keyGenerator;
    }

    /**
     * Create a new enterprise
     */
    public function create(string $name): Enterprise
    {
        $key = $this->keyGenerator->generateUniqueKey($name);

        return Enterprise::create([
            'name' => $name,
            'key' => $key,
            'status' => true,
        ]);
    }
}