<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserFilterService
{
    /**
     * Applique les filtres à la requête des utilisateurs
     * @param Builder|HasMany $query
     */
    public function applyFilters(Builder|HasMany $query, array $filters): Builder|HasMany
    {
        // Filtre par rôle
        if (isset($filters['role_uuid'])) {
            $query->where('role_uuid', $filters['role_uuid']);
        }

        // Filtre par email
        if (isset($filters['email'])) {
            $query->where('email', 'LIKE', "%{$filters['email']}%");
        }

        // Filtre par nom
        if (isset($filters['name'])) {
            $query->where(function($q) use ($filters) {
                $q->where('first_name', 'LIKE', "%{$filters['name']}%")
                    ->orWhere('last_name', 'LIKE', "%{$filters['name']}%");
            });
        }

        // Filtre par statut
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Tri
        $sortField = $filters['sort_by'] ?? 'first_name';
        $sortDirection = $filters['sort_direction'] ?? 'asc';
        
        $allowedSortFields = ['first_name', 'last_name', 'email', 'joined_at'];
        if (in_array($sortField, $allowedSortFields)) {
            $query->orderBy($sortField, $sortDirection === 'desc' ? 'desc' : 'asc');
        }

        return $query;
    }
}