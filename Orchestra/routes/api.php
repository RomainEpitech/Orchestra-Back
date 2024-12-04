<?php

use App\Http\Controllers\EnterpriseController;
use Illuminate\Support\Facades\Route;

Route::post('/newEnterprise', [EnterpriseController::class, 'store']);