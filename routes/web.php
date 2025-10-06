<?php

use App\Http\Controllers\ActivityLogsController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RolesController;
use App\Http\Controllers\UserRolesAndPermissionsController;
use App\Http\Controllers\UsersController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Notification routes
    Route::get('/notifications', [\App\Http\Controllers\NotificationsController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{id}/mark-as-read', [\App\Http\Controllers\NotificationsController::class, 'markAsRead'])->name('notifications.markAsRead');
    Route::post('/notifications/mark-many-as-read', [\App\Http\Controllers\NotificationsController::class, 'markManyAsRead'])->name('notifications.markManyAsRead');
    Route::delete('/notifications/{id}', [\App\Http\Controllers\NotificationsController::class, 'destroy'])->name('notifications.destroy');
    Route::delete('/notifications', [\App\Http\Controllers\NotificationsController::class, 'destroyAll'])->name('notifications.destroyAll');

    Route::name('admin.')->group(function () {
        Route::resource('users', UsersController::class);
        Route::put('user/{user}/update-role', [UserRolesAndPermissionsController::class, 'changeRole'])
            ->name('user.update-role');
        Route::put('user/{user}/update-permissions', [UserRolesAndPermissionsController::class, 'changePermissions'])
            ->name('user.update-permissions');
        Route::put('user/{user}/activate', [UsersController::class, 'activate'])
            ->name('user.activate');
        Route::put('user/{user}/ban', [UsersController::class, 'ban'])
            ->name('user.ban');

        Route::resource('roles', RolesController::class);
        Route::get('/activity-logs', [ActivityLogsController::class, 'index'])->name('activity-logs.index');
    });
});

require __DIR__.'/auth.php';
