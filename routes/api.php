<?php

use App\Http\Controllers\Api\V1\AnalyticsController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DriverLocationController;
use App\Http\Controllers\Api\V1\RouteController;
use App\Http\Controllers\Api\V1\ShipmentController;
use App\Http\Controllers\Api\V1\WarehouseScanningController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);

        Route::get('/user', fn (Request $request) => $request->user());

        Route::post('/shipments', [ShipmentController::class, 'store']);
        Route::get('/shipments/search', [AnalyticsController::class, 'searchShipments']);

        Route::post('/subscriptions/checkout', [\App\Http\Controllers\Api\V1\SubscriptionController::class, 'checkout'])
            ->middleware('role:Company Admin');

        Route::get('/analytics/metrics', [AnalyticsController::class, 'metrics']);

        Route::post('/routes', [RouteController::class, 'store']);
        Route::post('/routes/{route}/start', [RouteController::class, 'start']);
        Route::patch('/routes/{route}/stops', [RouteController::class, 'reorder']);

        Route::post('/warehouses/check-in', [WarehouseScanningController::class, 'checkIn']);
        Route::post('/warehouses/transfer', [WarehouseScanningController::class, 'transfer']);

        Route::post('/drivers/location', [DriverLocationController::class, 'store']);
    });
});
