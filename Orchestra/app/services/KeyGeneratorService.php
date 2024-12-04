<?php

namespace App\Services;

use Illuminate\Support\Str;
use App\Models\Enterprise;

class KeyGeneratorService
{
    /**
     * Generate a unique encrypted key for enterprise
     */
    public function generateUniqueKey(string $name): string
    {
        do {
            $baseKey = Str::slug($name);
            $randomStr = Str::random(8);
            $key = $baseKey . '-' . $randomStr;
            $encryptedKey = hash('sha256', $key);
            $finalKey = substr($encryptedKey, 0, 16);
            
        } while (Enterprise::where('key', $finalKey)->exists());

        return $finalKey;
    }
}