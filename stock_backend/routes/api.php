<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Auth\UserController;
use App\Http\Controllers\Auth\RoleController;
use App\Http\Controllers\Auth\PermissionController;
use App\Http\Controllers\Inventory\CategoryController;
use App\Http\Controllers\Inventory\ProductController;
use App\Http\Controllers\Purchasing\SupplierController;
use App\Http\Controllers\Purchasing\PurchaseOrderController;
use App\Http\Controllers\Sales\SaleController;
use App\Http\Controllers\Sales\PaymentController;

// Optional: default route Laravel gives you
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// API v1 group (optional but nice)
Route::prefix('v1')->group(function () {

    // AUTH MODULE
    Route::apiResource('users', UserController::class);
    Route::apiResource('roles', RoleController::class);
    Route::apiResource('permissions', PermissionController::class);

    // INVENTORY MODULE
    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('products', ProductController::class);

    // PURCHASING MODULE
    Route::apiResource('suppliers', SupplierController::class);
    Route::apiResource('purchase-orders', PurchaseOrderController::class);

    // SALES MODULE
    Route::apiResource('sales', SaleController::class);
    Route::apiResource('payments', PaymentController::class);
});
