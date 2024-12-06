<?php

namespace Tests\Feature;

use App\Models\Enterprise;
use App\Models\Role;
use App\Models\User;
use App\Models\Module;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;

class EnterpriseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Seed les données nécessaires
        $this->seed(\Database\Seeders\ModuleSeeder::class);
        $this->seed(\Database\Seeders\RoleSeeder::class);
    }

    /** @test */
    public function it_can_create_enterprise_with_admin()
    {
        $response = $this->postJson('/api/newEnterprise', [
            'enterprise_name' => 'Test Corp',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@testcorp.com',
            'password' => 'Password123'
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'enterprise' => [
                        'uuid',
                        'name',
                        'key'
                    ],
                    'user' => [
                        'uuid',
                        'first_name',
                        'last_name',
                        'email',
                        'role' => [
                            'name',
                            'authority'
                        ]
                    ]
                ]
            ]);

        // Vérifie que l'utilisateur a le rôle admin
        $this->assertEquals('administrateur', $response['data']['user']['role']['name']);
    }

    /** @test */
    public function it_prevents_access_to_non_member_users()
    {
        // 1. Créons deux entreprises manuellement
        $enterprise1 = Enterprise::create([
            'name' => 'Enterprise 1',
            'key' => 'key1',
            'status' => true
        ]);

        $enterprise2 = Enterprise::create([
            'name' => 'Enterprise 2',
            'key' => 'key2',
            'status' => true
        ]);

        // 2. Créons un utilisateur pour enterprise1
        $role = Role::where('name', 'administrateur')
            ->where('is_default', true)
            ->first();

        $user = User::create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'password' => bcrypt('password'),
            'role_uuid' => $role->uuid,
            'enterprise_uuid' => $enterprise1->uuid,
            'status' => true,
            'joined_at' => now()
        ]);

        // 3. Authentifions l'utilisateur
        Sanctum::actingAs($user);

        // 4. Essayons d'accéder à enterprise2
        $response = $this->getJson('/api/enterprise', [
            'Enterprise-Key' => $enterprise2->key
        ]);

        // 5. Vérifions que l'accès est refusé
        $response->assertStatus(403)
            ->assertJson(['message' => 'You are not a member of this enterprise']);
    }

    /** @test */
    public function it_prevents_access_without_proper_authority()
    {
        // 1. Créer une entreprise
        $enterprise = Enterprise::create([
            'name' => 'Test Enterprise',
            'key' => 'test-key',
            'status' => true
        ]);

        // 2. Récupérer le rôle membre (qui n'a pas l'autorité enterprise.read)
        $memberRole = Role::where('name', 'membre')
            ->where('is_default', true)
            ->first();

        // 3. Créer un utilisateur avec le rôle membre
        $user = User::create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@member.com',
            'password' => bcrypt('password'),
            'role_uuid' => $memberRole->uuid,
            'enterprise_uuid' => $enterprise->uuid,
            'status' => true,
            'joined_at' => now()
        ]);

        // 4. Authentifier l'utilisateur
        Sanctum::actingAs($user);

        // 5. Tenter d'accéder à la route protégée
        $response = $this->getJson('/api/enterprise', [
            'Enterprise-Key' => $enterprise->key
        ]);

        // 6. Vérifier que l'accès est refusé à cause des autorisations
        $response->assertStatus(403)
            ->assertJson([
                'message' => "Access denied: Requires enterprise.read permission"
            ]);
    }

    /** @test */
    public function it_allows_access_with_proper_authority()
    {
        // 1. Créer une entreprise
        $enterprise = Enterprise::create([
            'name' => 'Test Enterprise',
            'key' => 'test-key',
            'status' => true
        ]);

        // 2. Récupérer le rôle admin (qui a l'autorité enterprise.read)
        $adminRole = Role::where('name', 'administrateur')
            ->where('is_default', true)
            ->first();

        // 3. Créer un utilisateur avec le rôle admin
        $user = User::create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@admin.com',
            'password' => bcrypt('password'),
            'role_uuid' => $adminRole->uuid,
            'enterprise_uuid' => $enterprise->uuid,
            'status' => true,
            'joined_at' => now()
        ]);

        // 4. Authentifier l'utilisateur
        Sanctum::actingAs($user);

        // 5. Tenter d'accéder à la route protégée
        $response = $this->getJson('/api/enterprise', [
            'Enterprise-Key' => $enterprise->key
        ]);

        // 6. Vérifier que l'accès est autorisé
        $response->assertStatus(200)
            ->assertJsonStructure([
                'enterprise' => [
                    'uuid',
                    'name',
                    'status',
                    'created_at'
                ],
                'modules',
                'users',
                'subscription',
                'purchased_modules'
            ]);
    }

    /** @test */
    public function it_prevents_access_with_invalid_enterprise_key()
    {
        // 1. Créer une entreprise avec son admin
        $enterprise = Enterprise::create([
            'name' => 'Test Enterprise',
            'key' => 'valid-key',
            'status' => true
        ]);

        $adminRole = Role::where('name', 'administrateur')
            ->where('is_default', true)
            ->first();

        $user = User::create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@admin.com',
            'password' => bcrypt('password'),
            'role_uuid' => $adminRole->uuid,
            'enterprise_uuid' => $enterprise->uuid,
            'status' => true,
            'joined_at' => now()
        ]);

        // 2. Authentifier l'utilisateur
        Sanctum::actingAs($user);

        // 3. Essayer d'accéder avec une mauvaise clé
        $response = $this->getJson('/api/enterprise', [
            'Enterprise-Key' => 'wrong-key'
        ]);

        // 4. Vérifier que l'accès est refusé
        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Invalid enterprise key'
            ]);

        // 5. Essayer d'accéder sans clé
        $response = $this->getJson('/api/enterprise');

        // 6. Vérifier que l'accès est refusé
        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Enterprise key is missing from headers'
            ]);

        // 7. Essayer avec la bonne clé
        $response = $this->getJson('/api/enterprise', [
            'Enterprise-Key' => 'valid-key'
        ]);

        // 8. Vérifier que l'accès est autorisé
        $response->assertStatus(200)
            ->assertJsonStructure([
                'enterprise' => [
                    'uuid',
                    'name',
                    'status',
                    'created_at'
                ],
                'modules',
                'users',
                'subscription',
                'purchased_modules'
            ]);
    }

    /** @test */
    public function it_allows_access_with_valid_enterprise_key()
    {
        // 1. Créer une entreprise
        $enterprise = Enterprise::create([
            'name' => 'Test Enterprise',
            'key' => 'valid-key-123',
            'status' => true
        ]);

        // 2. Créer un utilisateur admin
        $adminRole = Role::where('name', 'administrateur')
            ->where('is_default', true)
            ->first();

        $user = User::create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@enterprise.com',
            'password' => bcrypt('password'),
            'role_uuid' => $adminRole->uuid,
            'enterprise_uuid' => $enterprise->uuid,
            'status' => true,
            'joined_at' => now()
        ]);

        // 3. Authentifier l'utilisateur
        Sanctum::actingAs($user);

        // 4. Tester l'accès avec la bonne clé
        $response = $this->getJson('/api/enterprise', [
            'Enterprise-Key' => 'valid-key-123'
        ]);

        // 5. Vérifier la réponse complète
        $response->assertStatus(200)
            ->assertJsonStructure([
                'enterprise' => [
                    'uuid',
                    'name',
                    'status',
                    'created_at'
                ],
                'modules',
                'users',
                'subscription',
                'purchased_modules'
            ])
            ->assertJson([
                'enterprise' => [
                    'name' => 'Test Enterprise',
                    'status' => true
                ]
            ]);

        // 6. Vérifier que les données correspondent à l'entreprise
        $responseData = $response->json();
        $this->assertEquals($enterprise->uuid, $responseData['enterprise']['uuid']);
        $this->assertEquals($enterprise->name, $responseData['enterprise']['name']);
    }

    /** @test */
    public function it_returns_complete_enterprise_data_when_all_middlewares_pass()
    {
        // 1. Créer une entreprise avec ses modules
        $enterprise = Enterprise::create([
            'name' => 'Full Test Enterprise',
            'key' => 'complete-test-key',
            'status' => true
        ]);

        // Assigner les modules à l'entreprise
        $moduleAssignmentService = new \App\Services\ModuleAssignmentService();
        $moduleAssignmentService->assignModules($enterprise);

        // 2. Créer l'admin
        $adminRole = Role::where('name', 'administrateur')
            ->where('is_default', true)
            ->first();

        $admin = User::create([
            'first_name' => 'Admin',
            'last_name' => 'User',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role_uuid' => $adminRole->uuid,
            'enterprise_uuid' => $enterprise->uuid,
            'status' => true,
            'joined_at' => now()
        ]);

        // 3. Créer un utilisateur standard
        $memberRole = Role::where('name', 'membre')
            ->where('is_default', true)
            ->first();

        $member = User::create([
            'first_name' => 'Regular',
            'last_name' => 'User',
            'email' => 'user@test.com',
            'password' => bcrypt('password'),
            'role_uuid' => $memberRole->uuid,
            'enterprise_uuid' => $enterprise->uuid,
            'status' => true,
            'joined_at' => now()
        ]);

        // 4. Authentifier l'admin
        Sanctum::actingAs($admin);

        // 5. Faire la requête
        $response = $this->getJson('/api/enterprise', [
            'Enterprise-Key' => 'complete-test-key'
        ]);

        // 6. Vérifier la structure et le contenu complet de la réponse
        $response->assertStatus(200)
            ->assertJsonStructure([
                'enterprise' => [
                    'uuid',
                    'name',
                    'status',
                    'created_at'
                ],
                'modules' => [
                    '*' => [
                        'uuid',
                        'name',
                        'key',
                        'is_core',
                        'purchase_price',
                        'is_activated',
                        'limits'
                    ]
                ],
                'users' => [
                    '*' => [
                        'uuid',
                        'first_name',
                        'last_name',
                        'email',
                        'status',
                        'joined_at',
                        'role' => [
                            'name',
                            'color_hex'
                        ]
                    ]
                ],
                'subscription' => [
                    'active',
                    'expires_at'
                ],
                'purchased_modules'
            ]);

        $responseData = $response->json();
        
        // 7. Vérifications supplémentaires
        // Vérifier le nombre d'utilisateurs
        $this->assertCount(2, $responseData['users']);
        
        // Vérifier que les modules sont présents
        $this->assertNotEmpty($responseData['modules']);
        
        // Vérifier que les modules core sont activés
        foreach ($responseData['modules'] as $module) {
            if ($module['is_core']) {
                $this->assertTrue(
                    (bool) $module['is_activated'],
                    "Module core '{$module['key']}' devrait être activé"
                );                
            }
        }

        // Vérifier les données spécifiques
        $this->assertEquals($enterprise->uuid, $responseData['enterprise']['uuid']);
        $this->assertTrue(collect($responseData['users'])->contains('email', 'admin@test.com'));
        $this->assertTrue(collect($responseData['users'])->contains('email', 'user@test.com'));
    }
}