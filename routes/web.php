<?php

use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Rsc\ProfileController as RscProfileController;
use App\Http\Controllers\Rsc\SolicitacaoRscController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    Route::prefix('admin')->name('admin.')->middleware('role:Administrador')->group(function () {
        Route::resource('users', UserController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('roles', RoleController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('permissions', PermissionController::class)->only(['index', 'store', 'update', 'destroy']);
    });

    Route::prefix('rsc')->name('rsc.')->group(function () {
        Route::get('perfil', [RscProfileController::class, 'edit'])->name('profile.edit');
        Route::put('perfil', [RscProfileController::class, 'update'])->name('profile.update');
        Route::resource('solicitacoes', SolicitacaoRscController::class)
            ->parameters(['solicitacoes' => 'solicitacao'])
            ->only(['index', 'create', 'store', 'show', 'edit', 'update']);
    });
});

require __DIR__.'/settings.php';
