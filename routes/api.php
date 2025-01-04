<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\GroupController;
use App\Http\Controllers\Api\DevicesController;
use App\Http\Controllers\Api\RolesController;
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


Route::middleware('jwt.verify')->prefix('dashboard')->group(function () {

    Route::prefix('users')->group(function () {
        // List Users
        Route::get('list', [AdminController::class, 'list']);

        // User Details
        Route::get('details/{id}', [AdminController::class, 'details']);

        // User Profile
        Route::get('profile/{id}', [AdminController::class, 'profile']);

        // Add User
        Route::post('add', [AdminController::class, 'add']);

        // Edit User
        Route::post('edit/{id}', [AdminController::class, 'edit']);

        // Edit Profile
        Route::post('edit-profile/{id}', [AdminController::class, 'editProfile']);

        // Activate/Deactivate User
        Route::post('active/{id}', [AdminController::class, 'active']);

        // Delete User
        Route::post('delete/{id}', [AdminController::class, 'delete']);

        // Invite User
        Route::post('invite/{id}', [AdminController::class, 'invite']);

        // Reset Password
        Route::post('reset-password/{id}', [AdminController::class, 'resetPassword']);

        // Refresh User Data
        Route::get('refresh', [AdminController::class, 'refresh']);
    });

    Route::post('group/add', [GroupController::class, 'create']);
    Route::get('group/list', [GroupController::class, 'list']);
    Route::get('group/all', [GroupController::class, 'all']);
    Route::post('group/activate/{id}', [GroupController::class, 'activate']);
    Route::post('group/update/{id}', [GroupController::class, 'update']);
    Route::delete('group/delete/{id}', [GroupController::class, 'delete']);

    Route::get('devices/list', [DevicesController::class, 'list']);
    Route::post('devices/add', [DevicesController::class, 'add']);
    Route::post('devices/update/{id}', [DevicesController::class, 'edit']);
    Route::delete('devices/delete/{id}', [DevicesController::class, 'delete']);
    Route::post('devices/import', [DevicesController::class, 'import']);

    Route::prefix('roles')->group(function () {
        Route::get('list', [RolesController::class, 'list']);
        Route::get('details/{id}', [RolesController::class, 'details']);
        Route::post('add', [RolesController::class, 'add']);
        Route::post('edit/{id}', [RolesController::class, 'edit']);
        Route::post('delete/{id}', [RolesController::class, 'delete']);
    });

});



