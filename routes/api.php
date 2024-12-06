<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\GroupController;
use App\Http\Controllers\Api\HostController;
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
    Route::middleware('api')->post('/logout', [AuthController::class, 'logout'])->name('logout'); // Optional, for consistency
});


Route::middleware('jwt.verify')->prefix('dashboard')->group(function () {
    Route::get('/admin', [AdminController::class, 'index']);
    Route::post('/admin/create', [AdminController::class, 'store']);
    Route::post('admin/activate/{id}', [AdminController::class, 'activate']);
    Route::post('/admin/update/{id}', [AdminController::class, 'update']);
    Route::post('/admin/delete/{id}', [AdminController::class, 'destroy']);

    Route::post('group/add', [GroupController::class, 'create']);
    Route::get('group/list', [GroupController::class, 'list']);
    Route::get('group/all', [GroupController::class, 'all']);
    Route::post('group/activate/{id}', [GroupController::class, 'activate']);
    Route::post('group/update/{id}', [GroupController::class, 'update']);
    Route::delete('group/delete/{id}', [GroupController::class, 'delete']);

    Route::get('hosts/list', [HostController::class, 'list']);
    Route::get('hosts/all', [HostController::class, 'all']);
    Route::post('hosts/add', [HostController::class, 'add']);
    Route::post('hosts/update/{id}', [HostController::class, 'update']);
    Route::delete('hosts/delete/{id}', [HostController::class, 'delete']);
    Route::post('hosts/import', [HostController::class, 'import']);
});



