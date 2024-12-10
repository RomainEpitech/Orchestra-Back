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
            
            // Charge les relations nécessaires pour les statistiques
            $enterprise->load([
                'modules' => fn($q) => $q->where('is_activated', true),
                'users',
                'subscriptions' => fn($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now())
            ]);

            // Prépare les statistiques pour chaque module activé
            $statistics = [
                'total_users' => $enterprise->users->count(),
                'active_modules' => $enterprise->modules->count(),
            ];

            // Ajoute des statistiques selon les modules activés
            if ($enterprise->modules->contains('key', 'events')) {
                $statistics['upcoming_events'] = $enterprise->events()
                    ->where('start_at', '>', now())
                    ->count();
            }

            if ($enterprise->modules->contains('key', 'absence')) {
                $statistics['pending_absences'] = $enterprise->absences()
                    ->where('status', 0)
                    ->count();
            }

            if ($enterprise->modules->contains('key', 'tasks')) {
                $statistics['ongoing_tasks'] = $enterprise->tasks()
                    ->whereNull('ended_at')
                    ->count();
            }

            return response()->json([
                'enterprise' => [
                    'uuid' => $enterprise->uuid,
                    'name' => $enterprise->name,
                    'status' => $enterprise->status,
                    'created_at' => $enterprise->created_at,
                ],
                'active_modules' => $enterprise->modules->pluck('key'),
                'subscription' => [
                    'active' => $enterprise->subscriptions->isNotEmpty(),
                    'expires_at' => $enterprise->subscriptions->first()?->expires_at
                ],
                'statistics' => $statistics
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error retrieving enterprise data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update enterprsie data
     */
    public function update(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|min:2|max:255'
            ]);

            $enterprise = $request->enterprise;
            $enterprise->update([
                'name' => $validated['name']
            ]);

            return response()->json([
                'message' => 'Enterprise name updated successfully',
                'enterprise' => [
                    'uuid' => $enterprise->uuid,
                    'name' => $enterprise->name
                ]
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error updating enterprise name',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}