<?php

namespace Database\Factories;

use App\Models\Enterprise;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'email' => $this->faker->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
            'status' => true,
            'joined_at' => now(),
            'leave_days' => 0
        ];
    }

    public function withRole(string $roleName = 'membre'): static
    {
        return $this->state(function (array $attributes) use ($roleName) {
            return [
                'role_uuid' => Role::where('name', $roleName)->first()->uuid
            ];
        });
    }
}