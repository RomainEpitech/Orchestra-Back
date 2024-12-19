<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\EnterpriseController;
use App\Http\Controllers\PersonnelModuleController;
use App\Http\Middleware\CheckEnterpriseKey;
use App\Http\Middleware\CheckEnterpriseMembership;
use App\Http\Middleware\CheckModuleAuthority;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/new-enterprise', [EnterpriseController::class, 'store']);

Route::middleware(['auth:sanctum', CheckEnterpriseKey::class, CheckEnterpriseMembership::class])
    ->group(function () {
        Route::middleware([CheckModuleAuthority::parameters('enterprise', 'read')])
            ->group(function () {
                Route::get('/enterprise', [EnterpriseController::class, 'show']);
            });
        
        Route::middleware([CheckModuleAuthority::parameters('enterprise', 'edit')])
            ->group(function () {
                Route::put('/enterprise', [EnterpriseController::class, 'update']);
            });

        Route::delete('/enterprise', [EnterpriseController::class, 'destroy']);

        Route::middleware([CheckModuleAuthority::parameters('personnel', 'create')])
            ->group(function () {
                Route::post('/enterprise/new-user', [PersonnelModuleController::class, 'registerPersonnel']);
            });

        Route::middleware([CheckModuleAuthority::parameters('personnel', 'read')])
            ->group(function () {
                Route::get('/enterprise/get-personnel', [PersonnelModuleController::class, 'getPersonnel']);
            });

        Route::middleware([CheckModuleAuthority::parameters('personnel', 'delete')])
            ->group(function () {
                Route::delete('/enterprise/delete-personnel/{uuid}', [PersonnelModuleController::class, 'destroyPersonnel']);
            });
        
        Route::middleware([CheckModuleAuthority::parameters('personnel', 'edit')])
            ->group(function () {
                Route::put('/enterprise/update-personnel/{uuid}', [PersonnelModuleController::class, 'updatePersonnel']);
            });
    }
);