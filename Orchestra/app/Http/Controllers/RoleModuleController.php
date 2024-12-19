<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Services\RoleManagementService;
use App\Exceptions\RoleLimitExceededException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RoleModuleController extends Controller 
{
    public function __construct(
        private RoleManagementService $roleService
    ) {}

    public function newRole(Request $request): JsonResponse
    {
        try {
            // Récupérer les limites du module
            $moduleLimits = $request->enterprise->modules()
                ->where('key', 'roles')
                ->first()
                ->limits
                ?->free_limit;

            $availableColors = $moduleLimits['availableColors'] ?? [];

            $validated = Validator::make($request->all(), [
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('roles', 'name')
                        ->where('enterprise_uuid', $request->enterprise->uuid)
                ],
                'color_hex' => [
                    'required',
                    'string',
                    'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/',
                    Rule::in($availableColors)
                ],
                'authority' => 'present|array'
            ], [
                'color_hex.in' => 'The selected color is not available in your subscription plan.'
            ])->validate();

            $role = $this->roleService->createRole(
                $validated,
                $request->enterprise
            );

            return response()->json([
                'message' => 'Role created successfully',
                'role' => [
                    'uuid' => $role->uuid,
                    'name' => $role->name,
                    'color_hex' => $role->color_hex,
                    'authority' => $role->authority
                ]
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (RoleLimitExceededException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'current_count' => $e->getCurrentCount(),
                'limit' => $e->getLimit()
            ], 403);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error creating role',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}