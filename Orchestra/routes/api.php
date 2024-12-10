<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\EnterpriseController;
use App\Http\Middleware\CheckEnterpriseKey;
use App\Http\Middleware\CheckEnterpriseMembership;
use App\Http\Middleware\CheckModuleAuthority;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/newEnterprise', [EnterpriseController::class, 'store']);

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
    }
);