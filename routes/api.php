<?php

use App\Http\Controllers\CodeController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\OptionalAuthSanctum;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RestaurantController;
use App\Http\Controllers\RestaurantUserController;
use App\Http\Controllers\WorkScheduleController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\StaffShiftController;
use App\Http\Controllers\HallController;
use App\Http\Controllers\ZoneController;
use App\Http\Controllers\TableController;
use App\Http\Controllers\StaffAssignmentController;
use App\Http\Controllers\MenuCategoryController;
use App\Http\Controllers\MenuItemController;


Route::group(['prefix' => 'auth'], function () {
    Route::middleware('guest')->group(function () {
        Route::post('authByPhone', [AuthController::class, 'authByPhone']);
    });
});


Route::middleware(OptionalAuthSanctum::class)->group(function () {

    Route::post('/code/send', [CodeController::class, 'send']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/me', [UserController::class, 'index']);
    Route::post('/accept', [UserController::class, 'accept']);
    Route::post('/logout', [AuthController::class, 'logout']);
    // Restaurants
    Route::get('/restaurants', [RestaurantController::class, 'index']);
    Route::post('/restaurants', [RestaurantController::class, 'store']);
    Route::get('/restaurants/{restaurant}', [RestaurantController::class, 'show']);
    Route::put('/restaurants/{restaurant}', [RestaurantController::class, 'update']);
    Route::delete('/restaurants/{restaurant}', [RestaurantController::class, 'destroy']);

    // Attach/detach users
    Route::post('/restaurants/{restaurant}/users', [RestaurantUserController::class, 'attachUser']);
    Route::delete('/restaurants/{restaurant}/users/{user}', [RestaurantUserController::class, 'detachUser']);

    // Work schedule + exceptions
    Route::get('/restaurants/{restaurant}/work-schedule', [WorkScheduleController::class, 'index']);
    Route::put('/restaurants/{restaurant}/work-schedule', [WorkScheduleController::class, 'upsertWeek']);
    Route::get('/restaurants/{restaurant}/work-exceptions', [WorkScheduleController::class, 'exceptions']);
    Route::post('/restaurants/{restaurant}/work-exceptions', [WorkScheduleController::class, 'storeException']);
    Route::delete('/restaurants/{restaurant}/work-exceptions/{id}', [WorkScheduleController::class, 'deleteException']);

    // Staff & shifts
    Route::get('/restaurants/{restaurant}/staff', [StaffController::class, 'index']);
    Route::post('/restaurants/{restaurant}/staff', [StaffController::class, 'store']);
    Route::get('/restaurants/{restaurant}/staff/{staff}', [StaffController::class, 'show']);
    Route::put('/restaurants/{restaurant}/staff/{staff}', [StaffController::class, 'update']);
    Route::delete('/restaurants/{restaurant}/staff/{staff}', [StaffController::class, 'destroy']);

    Route::get('/restaurants/{restaurant}/staff/{staff}/shifts', [StaffShiftController::class, 'index']);
    Route::post('/restaurants/{restaurant}/staff/{staff}/shifts', [StaffShiftController::class, 'store']);
    Route::delete('/restaurants/{restaurant}/staff/{staff}/shifts/{shift}', [StaffShiftController::class, 'destroy']);

    // Halls / Zones / Tables
    Route::apiResource('/restaurants/{restaurant}/halls', HallController::class);
    Route::apiResource('/halls/{hall}/zones', ZoneController::class);
    Route::apiResource('/zones/{zone}/tables', TableController::class);

    // Staff assignments
    Route::post('/staff/{staff}/zones', [StaffAssignmentController::class, 'attachZones']);
    Route::post('/staff/{staff}/tables', [StaffAssignmentController::class, 'attachTables']);
    Route::delete('/staff/{staff}/zones/{zone}', [StaffAssignmentController::class, 'detachZone']);
    Route::delete('/staff/{staff}/tables/{table}', [StaffAssignmentController::class, 'detachTable']);

    // Menu
    Route::apiResource('/restaurants/{restaurant}/menu-categories', MenuCategoryController::class);
    Route::apiResource('/menu-categories/{category}/items', MenuItemController::class);

    // Availability
    Route::get('/restaurants/{restaurant}/availability', [ReservationController::class, 'availability']);

// Reservations CRUD
    Route::get('/restaurants/{restaurant}/reservations', [ReservationController::class, 'index']);
    Route::post('/restaurants/{restaurant}/reservations', [ReservationController::class, 'store']);
    Route::get('/restaurants/{restaurant}/reservations/{reservation}', [ReservationController::class, 'show']);
    Route::put('/restaurants/{restaurant}/reservations/{reservation}', [ReservationController::class, 'update']);
    Route::delete('/restaurants/{restaurant}/reservations/{reservation}', [ReservationController::class, 'destroy']);

});
