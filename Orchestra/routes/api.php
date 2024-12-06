<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\EnterpriseController;
use App\Http\Middleware\CheckEnterpriseKey;
use App\Http\Middleware\CheckEnterpriseMembership;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/newEnterprise', [EnterpriseController::class, 'store']);

Route::middleware(['auth:sanctum', CheckEnterpriseKey::class, CheckEnterpriseMembership::class])
    ->group(function () {
        Route::get('/enterprise', [EnterpriseController::class, 'show']);
    }
);