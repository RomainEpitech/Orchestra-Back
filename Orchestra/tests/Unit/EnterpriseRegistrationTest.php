<?php

namespace Tests\Unit;

use App\Models\Enterprise;
use App\Models\Role;
use App\Models\User;
use App\Models\Module;
use App\Services\EnterpriseRegistrationService;
use App\Services\ModuleAssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnterpriseRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected $registrationService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed les données nécessaires
        $this->seed(\Database\Seeders\ModuleSeeder::class);
        $this->seed(\Database\Seeders\RoleSeeder::class);

        $this->registrationService = new EnterpriseRegistrationService(
            new ModuleAssignmentService()
        );
    }

    /** @test */
    public function it_can_register_a_new_enterprise_with_admin()
    {
        // Arrange
        $data = [
            'enterprise_name' => 'Test Enterprise',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@test.com',
            'password' => 'Password123'
        ];

        // Act
        $result = $this->registrationService->register($data);

        // Assert
        $this->assertDatabaseHas('enterprises', [
            'name' => 'Test Enterprise',
            'status' => true
        ]);

        $this->assertDatabaseHas('users', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@test.com'
        ]);

        // Vérifie que l'utilisateur a bien le rôle admin
        $user = User::where('email', 'john.doe@test.com')->first();
        $adminRole = Role::where('name', 'administrateur')
            ->where('is_default', true)
            ->first();
        
        $this->assertEquals($adminRole->uuid, $user->role_uuid);

        // Vérifie que les modules core sont activés
        $enterprise = Enterprise::where('name', 'Test Enterprise')->first();
        $coreModules = Module::where('is_core', true)->get();
        
        foreach ($coreModules as $module) {
            $this->assertTrue(
                $enterprise->modules()
                    ->wherePivot('module_uuid', $module->uuid)
                    ->wherePivot('is_activated', true)
                    ->exists()
            );
        }
    }

    /** @test */
    public function it_assigns_correct_modules_activation_status()
    {
        // Arrange
        $data = [
            'enterprise_name' => 'Test Enterprise',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@test.com',
            'password' => 'Password123'
        ];

        // Act
        $result = $this->registrationService->register($data);
        $enterprise = Enterprise::where('name', 'Test Enterprise')->first();

        // Assert
        Module::all()->each(function ($module) use ($enterprise) {
            $enterpriseModule = $enterprise->modules()
                ->wherePivot('module_uuid', $module->uuid)
                ->first();

            $this->assertNotNull($enterpriseModule);
            $this->assertEquals(
                $module->is_core,
                $enterpriseModule->pivot->is_activated,
                "Module {$module->name} activation status is incorrect"
            );
        });
    }

    /** @test */
    public function it_fails_if_email_already_exists()
    {
        // Arrange
        $data = [
            'enterprise_name' => 'Test Enterprise',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@test.com',
            'password' => 'Password123'
        ];

        // Premier enregistrement
        $this->registrationService->register($data);

        // Assert
        $this->expectException(\Illuminate\Database\QueryException::class);

        // Act - Tentative avec le même email
        $this->registrationService->register([
            'enterprise_name' => 'Another Enterprise',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'john.doe@test.com', // Même email
            'password' => 'Password123'
        ]);
    }

    /** @test */
    public function it_returns_correct_response_structure()
    {
        $data = [
            'enterprise_name' => 'Test Enterprise',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@test.com',
            'password' => 'Password123'
        ];

        // Act
        $result = $this->registrationService->register($data);

        // Assert
        $this->assertArrayHasKey('enterprise', $result);
        $this->assertArrayHasKey('user', $result);
        $this->assertArrayHasKey('modules', $result);

        // Vérifie que le mot de passe n'est pas visible dans la réponse
        $this->assertNotEquals(
            $data['password'],
            $result['user']['password'] ?? null,
            'Password should not be visible in plain text'
        );

        $this->assertNotEmpty($result['modules']);
        foreach ($result['modules'] as $module) {
            $this->assertArrayHasKey('name', $module);
            $this->assertArrayHasKey('key', $module);
            $this->assertArrayHasKey('is_activated', $module);
        }
    }
}