<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use App\Services\UserFilterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PersonnelModuleController extends Controller
{
    protected UserFilterService $filterService;

    public function __construct(UserFilterService $filterService)
    {
        $this->filterService = $filterService;
    }

    /**
     * Register new User
     */
    public function registerPersonnel(Request $request): JsonResponse
    {
        try {
            // 1. Validation des données
            $validated = $request->validate([
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => ['required', Password::min(8)->mixedCase()->numbers()],
                'role_uuid' => 'required|uuid|exists:roles,uuid',
                'joined_at' => 'required|date|before_or_equal:now',
            ]);

            // 2. Vérification que le rôle appartient bien à l'entreprise
            $role = Role::where('uuid', $validated['role_uuid'])
                ->where(function($query) use ($request) {
                    $query->where('enterprise_uuid', $request->enterprise->uuid)
                        ->orWhereNull('enterprise_uuid');
                })
                ->first();

            if (!$role) {
                return response()->json([
                    'message' => 'Invalid role for this enterprise'
                ], 422);
            }

            // 3. Vérification des limites du module Personnel
            $currentUsersCount = $request->enterprise->users()->count();
            $moduleLimit = $request->enterprise->modules()
                ->where('key', 'personnel')
                ->first()
                ->limits
                ?->free_limit['maxUsers'] ?? null;

            if ($moduleLimit && $currentUsersCount >= $moduleLimit) {
                return response()->json([
                    'message' => 'Users limit reached. Please upgrade your subscription.',
                    'current_count' => $currentUsersCount,
                    'limit' => $moduleLimit
                ], 403);
            }

            // 4. Création de l'utilisateur
            $user = DB::transaction(function () use ($validated, $request) {
                return User::create([
                    'first_name' => $validated['first_name'],
                    'last_name' => $validated['last_name'],
                    'email' => $validated['email'],
                    'password' => Hash::make($validated['password']),
                    'role_uuid' => $validated['role_uuid'],
                    'enterprise_uuid' => $request->enterprise->uuid,
                    'status' => true,
                    'joined_at' => $validated['joined_at'],
                    'leave_days' => 0 // Valeur par défaut ou configurable
                ]);
            });

            // 5. Retourner la réponse
            return response()->json([
                'message' => 'User created successfully',
                'user' => [
                    'uuid' => $user->uuid,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'role' => [
                        'name' => $role->name,
                        'color_hex' => $role->color_hex
                    ],
                    'joined_at' => $user->joined_at
                ]
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error creating user',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all personnel
     */
    public function getPersonnel(Request $request): JsonResponse 
    {
        try {
            // Valider les paramètres de filtrage
            $filters = $request->validate([
                'role' => 'sometimes|string|exists:roles,name',
                'email' => 'sometimes|string',
                'name' => 'sometimes|string',
                'status' => 'sometimes|boolean',
                'sort_by' => 'sometimes|string|in:first_name,last_name,email,joined_at',
                'sort_direction' => 'sometimes|string|in:asc,desc'
            ]);

            // Construire la requête
            $query = $request->enterprise->users()->with(['role:uuid,name,color_hex']);
            
            // Appliquer les filtres
            $query = $this->filterService->applyFilters($query, $filters);

            // Récupérer les résultats
            $users = $query->get()->map(function($user) {
                return [
                    'uuid' => $user->uuid,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'status' => $user->status,
                    'joined_at' => $user->joined_at->format('Y-m-d'),
                    'leave_days' => $user->leave_days,
                    'role' => [
                        'name' => $user->role->name,
                        'color_hex' => $user->role->color_hex
                    ]
                ];
            });

            return response()->json([
                'total_users' => $users->count(),
                'filters_applied' => array_keys($filters),
                'users' => $users
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Invalid filters',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error retrieving personnel',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}