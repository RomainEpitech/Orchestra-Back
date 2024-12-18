<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserFilterService
{
    public function applyFilters(Builder|HasMany $query, array $filters): Builder|HasMany
    {
        // Filtre par rôle
        if (isset($filters['role'])) {
            $query->whereHas('role', function($q) use ($filters) {
                $q->where('name', $filters['role']);
            });
        }

        // Filtre par statut
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Tri alphabétique
        $sortField = $filters['sort_by'] ?? 'last_name';  // Par défaut, tri par nom
        $sortDirection = $filters['sort_direction'] ?? 'asc';
        
        $allowedSortFields = ['last_name', 'first_name'];
        if (in_array($sortField, $allowedSortFields)) {
            $query->orderBy($sortField, $sortDirection);
            
            // Si on trie par nom, on ajoute un tri secondaire par prénom
            if ($sortField === 'last_name') {
                $query->orderBy('first_name', $sortDirection);
            }
        }

        return $query;
    }
}