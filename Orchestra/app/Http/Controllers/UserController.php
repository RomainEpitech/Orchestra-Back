<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UserController extends Controller {

    /**
     * Get logged user infos
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('role', 'enterprise');

        return response()->json([
            'user' => [
                'uuid' => $user->uuid,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'role' => [
                    'name' => $user->role->name,
                    'authority' => $user->role->authority
                ],
                'enterprise' => [
                    'name' => $user->enterprise->name
                ]
            ]
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        try {
            // L'utilisateur ne peut modifier que son propre profil
            $user = $request->user();

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

            // Password (optionnel, avec confirmation)
            if ($request->has('password')) {
                $validationRules['password'] = 'required|string|min:8|confirmed';
                $dataToUpdate['password'] = bcrypt($request->password);
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
                'message' => 'Profile updated successfully',
                'user' => [
                    'uuid' => $user->uuid,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
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
                'message' => 'Error updating profile',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}