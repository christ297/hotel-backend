<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChambreController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\UserController;


use App\Http\Controllers\OrderController;
use App\Http\Controllers\MenuItemController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\BarStatsController;
    
    Route::post('/users', [UserController::class, 'store']);
    Route::post('/users/login', [UserController::class, 'login']);


    Route::get('/chambre', [ChambreController::class, 'index']);
    Route::get('/chambre/{id}', [ChambreController::class, 'show']);

    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('/chambre', [ChambreController::class, 'store']);
        Route::put('/chambre/{id}', [ChambreController::class, 'update']);
        Route::delete('/chambre/{id}', [ChambreController::class, 'destroy']);

        Route::get('/users', [UserController::class, 'index']);
        Route::get('/users/{id}', [UserController::class, 'show']);
        Route::put('/users/{id}', [UserController::class, 'update']);
        Route::delete('/users/{id}', [UserController::class, 'destroy']);

        Route::post('/reservation', [ReservationController::class, 'store']);
        Route::get('/reservation', [ReservationController::class, 'index']);
        Route::get('/reservation/{id}', [ReservationController::class, 'show']);
        Route::put('/reservation/{id}', [ReservationController::class, 'update']);
        Route::delete('/reservation/{id}', [ReservationController::class, 'destroy']);
        Route::get('/search_reservations', [ReservationController::class, 'searchReservations']);
    });

        Route::post('/search_chambres', [ChambreController::class, 'searchChambres']);








//Route::middleware('auth:sanctum')->group(function () {
    // Commandes
    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::put('/orders/{order}/status', [OrderController::class, 'updateStatus']);
    Route::delete('/orders/{order}', [OrderController::class, 'destroy']);
    
    // Menu
    Route::get('/menu-items', [MenuItemController::class, 'index']);
    Route::post('/menu-items', [MenuItemController::class, 'store']);
    Route::put('/menu-items/{menuItem}', [MenuItemController::class, 'update']);
    Route::delete('/menu-items/{menuItem}', [MenuItemController::class, 'destroy']);
    
    // Catégories
    Route::get('/categories', [CategoryController::class, 'index']);
    
    // Statistiques
    Route::get('/bar-stats', [BarStatsController::class, 'index']);
//});




    Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
        return response()->json($request->user());
    });
    
