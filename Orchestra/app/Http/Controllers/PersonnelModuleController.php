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
use Illuminate\Validation\Rule;
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

    /**
     * Delete a user from enterprise
     */
    public function destroyPersonnel(Request $request, string $uuid): JsonResponse
    {
        try {
            $user = $request->enterprise->users()
                ->where('uuid', $uuid)
                ->first();

            if (!$user) {
                return response()->json([
                    'message' => 'User not found in this enterprise'
                ], 404);
            }

            // Empêcher la suppression de l'owner de l'entreprise
            if ($request->enterprise->owner_uuid === $user->uuid) {
                return response()->json([
                    'message' => 'Cannot delete the enterprise owner'
                ], 403);
            }

            // Empêcher l'auto-suppression
            if ($request->user()->uuid === $user->uuid) {
                return response()->json([
                    'message' => 'Cannot delete your own account'
                ], 403);
            }

            DB::transaction(function () use ($user) {
                // Supprimer les relations de l'utilisateur avant de le supprimer
                // Ex: participations aux événements, absences, etc.
                $user->delete();
            });

            return response()->json([
                'message' => 'User deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error deleting user',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a user from enterprise
     */
    public function updatePersonnel(Request $request, string $uuid): JsonResponse
    {
        try {
            // Trouver l'utilisateur
            $user = $request->enterprise->users()
                ->where('uuid', $uuid)
                ->first();

            if (!$user) {
                return response()->json([
                    'message' => 'User not found in this enterprise'
                ], 404);
            }

            // Valider seulement les champs présents dans la requête
            $validationRules = [];
            $dataToUpdate = [];

            // First Name
            if ($request->has('first_name')) {
                $validationRules['first_name'] = 'required|string|max:255';
                $dataToUpdate['first_name'] = $request->first_name;
            }

            // Last Name
            if ($request->has('last_name')) {
                $validationRules['last_name'] = 'required|string|max:255';
                $dataToUpdate['last_name'] = $request->last_name;
            }

            // Email (avec vérification d'unicité en excluant l'utilisateur actuel)
            if ($request->has('email')) {
                $validationRules['email'] = [
                    'required',
                    'email',
                    Rule::unique('users')->ignore($user->uuid, 'uuid')
                ];
                $dataToUpdate['email'] = $request->email;
            }

            // Role
            if ($request->has('role_uuid')) {
                $validationRules['role_uuid'] = 'required|uuid|exists:roles,uuid';
                
                // Vérifier que le rôle appartient à l'entreprise ou est un rôle par défaut
                $role = Role::where('uuid', $request->role_uuid)
                    ->where(function($query) use ($request) {
                        $query->where('enterprise_uuid', $request->enterprise->uuid)
                            ->orWhereNull('enterprise_uuid');
                    })->first();

                if (!$role) {
                    return response()->json([
                        'message' => 'Invalid role for this enterprise'
                    ], 422);
                }

                $dataToUpdate['role_uuid'] = $request->role_uuid;
            }

            // Status
            if ($request->has('status')) {
                $validationRules['status'] = 'required|boolean';
                $dataToUpdate['status'] = $request->status;
            }

            // Leave days
            if ($request->has('leave_days')) {
                $validationRules['leave_days'] = 'required|integer|min:0';
                $dataToUpdate['leave_days'] = $request->leave_days;
            }

            // Si aucun champ à mettre à jour
            if (empty($dataToUpdate)) {
                return response()->json([
                    'message' => 'No fields to update'
                ], 422);
            }

            // Valider les données
            $validated = $request->validate($validationRules);

            // Mettre à jour uniquement les champs modifiés
            $user->update($dataToUpdate);

            // Charger les relations nécessaires pour la réponse
            $user->load('role:uuid,name,color_hex');

            return response()->json([
                'message' => 'User updated successfully',
                'user' => [
                    'uuid' => $user->uuid,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'status' => $user->status,
                    'leave_days' => $user->leave_days,
                    'joined_at' => $user->joined_at->format('Y-m-d'),
                    'role' => [
                        'name' => $user->role->name,
                        'color_hex' => $user->role->color_hex
                    ]
                ]
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error updating user',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}