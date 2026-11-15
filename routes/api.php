<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\FinanceController;
use App\Http\Controllers\Api\V1\InventoryController;
use App\Http\Controllers\Api\V1\PosController;
use App\Http\Controllers\Api\V1\WorkshopController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes for Android Mobile & External Integrations
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {
    // Auth Routes
    Route::post('/auth/login', [AuthController::class, 'login']);

    // Protected Routes (Sanctum Token)
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);

        // Inventory & Barcode Scans for Android
        Route::prefix('inventory')->group(function () {
            Route::get('/quick-check', [InventoryController::class, 'quickCheck']);
            Route::post('/opname/scan', [InventoryController::class, 'recordOpnameScan']);
            Route::get('/stock-card', [InventoryController::class, 'stockCard']);
        });

        // Workshop & Work Orders (PKB) for Android
        Route::prefix('workshop')->group(function () {
            Route::get('/vehicles/search', [WorkshopController::class, 'searchVehicle']);
            Route::post('/vehicles', [WorkshopController::class, 'registerVehicle']);
            Route::get('/work-orders', [WorkshopController::class, 'listWorkOrders']);
            Route::post('/work-orders', [WorkshopController::class, 'createWorkOrder']);
            Route::get('/work-orders/{id}', [WorkshopController::class, 'showWorkOrder']);
            Route::post('/work-orders/{id}/items', [WorkshopController::class, 'addItem']);
            Route::patch('/work-orders/{id}/status', [WorkshopController::class, 'updateStatus']);
            Route::get('/mechanics/my-commissions', [WorkshopController::class, 'myCommissions']);
        });

        // POS Kasir for Android
        Route::prefix('pos')->group(function () {
            Route::get('/register/status', [PosController::class, 'registerStatus']);
            Route::post('/register/open', [PosController::class, 'openRegister']);
            Route::post('/register/close', [PosController::class, 'closeRegister']);
            Route::post('/checkout', [PosController::class, 'checkout']);
            Route::get('/suspended-bills', [PosController::class, 'listSuspendedBills']);
            Route::get('/ready-work-orders', [PosController::class, 'listReadyWorkOrders']);
        });

        // Finance & Purchasing for Android
        Route::prefix('finance')->group(function () {
            Route::get('/profit-loss', [FinanceController::class, 'profitLoss']);
            Route::get('/purchases', [FinanceController::class, 'listPurchases']);
            Route::post('/purchases', [FinanceController::class, 'createPurchase']);
            Route::post('/purchases/{id}/receive', [FinanceController::class, 'receivePurchase']);
        });
    });
});
