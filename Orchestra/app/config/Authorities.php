<?php

namespace App\Config;

class Authorities
{
    public const STRUCTURE = [
        'personnel' => [
            'read' => false,
            'create' => false,
            'edit' => false,
            'delete' => false,
        ],
        'roles' => [
            'read' => false,
            'create' => false,
            'edit' => false,
            'delete' => false,
            'assign_permissions' => false
        ],
        'events' => [
            'read' => false,
            'create' => false,
            'edit' => false,
            'delete' => false,
            'manage_participants' => false,
            'manage_rooms' => false
        ],
        'enterprise' => [
            'read' => false,
            'edit' => false,
            'manage_modules' => false,
            'manage_subscription' => false
        ],
        'absences' => [
            'read' => false,
            'create' => false,
            'edit' => false,
            'delete' => false,
            'approve' => false
        ],
        'taches' => [
            'read' => false,
            'create' => false,
            'edit' => false,
            'delete' => false
        ]
    ];

    public static function getDefault(): array 
    {
        return self::STRUCTURE;
    }
}