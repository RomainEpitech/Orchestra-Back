<?php

namespace App\Http\Controllers;

use App\Models\Enterprise;
use App\Services\KeyGeneratorService;
use App\Services\ModuleAssignmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EnterpriseController extends Controller
{
    /**
     * Create new enterprise
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|min:2|max:255'
            ]);

            return DB::transaction(function () use ($validated) {
                $enterprise = Enterprise::create([
                    'name' => $validated['name'],
                    'key' => KeyGeneratorService::generateUniqueKey($validated['name']),
                    'status' => true
                ]);

                ModuleAssignmentService::assignModules($enterprise);
                $enterprise->load('modules');

                return response()->json($enterprise, 201);
            });

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
            
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while creating the enterprise',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}