<?php

namespace App\Http\Controllers;

use App\Services\EnterpriseRegistrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class EnterpriseController extends Controller
{
    protected EnterpriseRegistrationService $registrationService;

    public function __construct(EnterpriseRegistrationService $registrationService)
    {
        $this->registrationService = $registrationService;
    }

    /**
     * Create new enterprise with admin user
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'enterprise_name' => 'required|string|min:2|max:255',
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => ['required', Password::min(8)->mixedCase()->numbers()]
            ]);

            return DB::transaction(function () use ($validated) {
                $result = $this->registrationService->register($validated);
                
                return response()->json([
                    'message' => 'Enterprise and admin user created successfully',
                    'data' => $result
                ], 201);
            });

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
            
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred during registration',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get enterprise data
     */
    public function show(Request $request): JsonResponse
    {
        try {
            $enterprise = $request->enterprise;

            $enterprise->load([
                'modules' => function($query) {
                    $query->with('limits');
                },
                'users' => function($query) {
                    $query->with('role:uuid,name,authority,color_hex');
                },
                'purchasedModules',
                'subscriptions' => function($query) {
                    $query->whereNull('expires_at')
                        ->orWhere('expires_at', '>', now());
                }
            ]);

            return response()->json([
                'enterprise' => [
                    'uuid' => $enterprise->uuid,
                    'name' => $enterprise->name,
                    'status' => $enterprise->status,
                    'created_at' => $enterprise->created_at,
                ],
                'modules' => $enterprise->modules->map(function($module) {
                    return [
                        'uuid' => $module->uuid,
                        'name' => $module->name,
                        'key' => $module->key,
                        'is_core' => $module->is_core,
                        'purchase_price' => $module->purchase_price,
                        'is_activated' => $module->pivot->is_activated,
                        'limits' => $module->limits?->free_limit
                    ];
                }),
                'users' => $enterprise->users->map(function($user) {
                    return [
                        'uuid' => $user->uuid,
                        'first_name' => $user->first_name,
                        'last_name' => $user->last_name,
                        'email' => $user->email,
                        'status' => $user->status,
                        'joined_at' => $user->joined_at,
                        'role' => [
                            'name' => $user->role->name,
                            'color_hex' => $user->role->color_hex
                        ]
                    ];
                }),
                'subscription' => [
                    'active' => $enterprise->subscriptions->isNotEmpty(),
                    'expires_at' => $enterprise->subscriptions->first()?->expires_at
                ],
                'purchased_modules' => $enterprise->purchasedModules->map(function($module) {
                    return [
                        'key' => $module->key,
                        'price' => $module->purchase_price,
                        'purchased_at' => $module->pivot->purchased_at
                    ];
                })
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error retrieving enterprise data',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}