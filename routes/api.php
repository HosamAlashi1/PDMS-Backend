<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\CommonController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\GroupController;
use App\Http\Controllers\Api\DevicesController;
use App\Http\Controllers\Api\MapController;
use App\Http\Controllers\Api\RolesController;
use App\Http\Controllers\Api\SettingsController;
use App\Http\Controllers\NotificationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::group([
    'prefix' => 'auth'
], function () {
    Route::post('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
    Route::post('/change-password', [AuthController::class, 'changePassword'])->middleware('jwt.verify');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

Route::get('/test-notification', [NotificationController::class, 'sendUserNotification']);

Route::middleware('jwt.verify')->group(function () {
    // Dashboard
    Route::get('dashboard/statistics', [DashboardController::class, 'statistics']);
    Route::get('dashboard/statistics-by-month', [DashboardController::class, 'detailedStatisticsByMonth']);


    Route::get('common/permissions', [CommonController::class, 'permissions']);
    Route::get('common/lookups', [CommonController::class, 'lookups']);

    // admins
    Route::prefix('users')->group(function () {
        Route::get('list', [AdminController::class, 'list']);
        Route::get('details/{id}', [AdminController::class, 'details']);
        Route::get('profile/{id}', [AdminController::class, 'profile']);
        Route::post('add', [AdminController::class, 'add']);
        Route::post('edit/{id}', [AdminController::class, 'edit']);
        Route::post('edit-profile/{id}', [AdminController::class, 'editProfile']);
        Route::post('active/{id}', [AdminController::class, 'active']);
        Route::post('delete/{id}', [AdminController::class, 'delete']);
        Route::post('invite/{id}', [AdminController::class, 'invite']);
        Route::post('reset-password/{id}', [AdminController::class, 'resetPassword']);
        Route::get('refresh', [AdminController::class, 'refresh']);
    });

    Route::post('group/add', [GroupController::class, 'create']);
    Route::get('group/list', [GroupController::class, 'list']);
    Route::get('group/all', [GroupController::class, 'all']);
    Route::post('group/activate/{id}', [GroupController::class, 'activate']);
    Route::post('group/update/{id}', [GroupController::class, 'update']);
    Route::post('group/delete/{id}', [GroupController::class, 'delete']);

    Route::get('devices/list', [DevicesController::class, 'list']);
    Route::post('devices/add', [DevicesController::class, 'add']);
    Route::post('devices/edit/{id}', [DevicesController::class, 'edit']);
    Route::post('devices/delete/{id}', [DevicesController::class, 'delete']);
    Route::post('devices/import', [DevicesController::class, 'import']);

    Route::prefix('roles')->group(function () {
        Route::get('list', [RolesController::class, 'list']);
        Route::get('details/{id}', [RolesController::class, 'details']);
        Route::post('add', [RolesController::class, 'add']);
        Route::post('edit/{id}', [RolesController::class, 'edit']);
        Route::post('delete/{id}', [RolesController::class, 'delete']);
    });

    Route::prefix('settings')->group(function () {
        Route::get('list', [SettingsController::class, 'list'])->name('settings.list');
        Route::post('edit', [SettingsController::class, 'edit'])->name('settings.edit');
    });

    Route::get('map/list', [MapController::class, 'list']);
});



