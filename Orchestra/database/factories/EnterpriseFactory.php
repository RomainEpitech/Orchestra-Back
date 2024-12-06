<?php

namespace Database\Factories;

use App\Models\Enterprise;
use Illuminate\Database\Eloquent\Factories\Factory;

class EnterpriseFactory extends Factory
{
    protected $model = Enterprise::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'key' => substr(hash('sha256', $this->faker->unique()->uuid()), 0, 16),
            'status' => true
        ];
    }
}