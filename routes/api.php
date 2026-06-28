<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Petugas\AuthController as PetugasAuthController;
use App\Http\Controllers\Api\V1\Petugas\SampahTerkelolaController;
use App\Http\Controllers\Api\V1\Petugas\SampahDiserahkanController;
use App\Http\Controllers\Api\V1\Master\MasterDataController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| API v1 routes untuk Flutter Mobile Application
| Base URL: /api/v1
|
*/

Route::prefix('v1')->group(function () {
    
    // ==================== PETUGAS AUTHENTICATION ====================
    Route::post('/login', [PetugasAuthController::class, 'login']);
    
    // Protected routes (memerlukan autentikasi)
    Route::middleware('auth:sanctum')->group(function () {
        
        // Petugas Auth routes
        Route::post('/logout', [PetugasAuthController::class, 'logout']);
        Route::get('/me', [PetugasAuthController::class, 'profile']);
        
        // ==================== PETUGAS SAMPAH TERKELOLA ====================
        Route::prefix('sampah-terkelola')->group(function () {
            Route::get('/', [SampahTerkelolaController::class, 'index']);
            Route::get('/{id}', [SampahTerkelolaController::class, 'show']);
            Route::post('/', [SampahTerkelolaController::class, 'store']);
            Route::put('/{id}', [SampahTerkelolaController::class, 'update']);
        });
        
        // ==================== PETUGAS SAMPAH DISERAHKAN ====================
        Route::prefix('sampah-diserahkan')->group(function () {
            Route::get('/', [SampahDiserahkanController::class, 'index']);
            Route::get('/{id}', [SampahDiserahkanController::class, 'show']);
            Route::post('/', [SampahDiserahkanController::class, 'store']);
            Route::put('/{id}', [SampahDiserahkanController::class, 'update']);
        });
    });
    
    // ==================== MASTER DATA (Public) ====================
    Route::get('/master-data', [MasterDataController::class, 'index']);
    
});
