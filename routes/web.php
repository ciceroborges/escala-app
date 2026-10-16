<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\MinistryController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\SetlistController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SongController;
use App\Http\Controllers\WorshipServiceController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'login'])->name('login');
    Route::get('/auth/google', [AuthController::class, 'redirect'])->name('auth.google');
    Route::get('/auth/google/callback', [AuthController::class, 'callback']);
    Route::post('/dev/login', [AuthController::class, 'devLogin'])->name('dev.login');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/ministerios', [MinistryController::class, 'index'])->name('ministries.index');
    Route::post('/ministerios', [MinistryController::class, 'store'])->name('ministries.store');
    Route::post('/ministerios/{ministry}/entrar', [MinistryController::class, 'switch'])->name('ministries.switch');

    Route::middleware('ministry')->group(function () {
        Route::get('/', DashboardController::class)->name('dashboard');

        Route::get('/escalas', [ScheduleController::class, 'index'])->name('schedules.index');
        Route::get('/escalas/{schedule}', [ScheduleController::class, 'show'])->name('schedules.show');
        Route::put('/escalas/{schedule}/disponibilidade', [ScheduleController::class, 'availability'])->name('schedules.availability');
        Route::get('/cultos/{service}', [WorshipServiceController::class, 'show'])->name('services.show');
        Route::get('/musicas', [SongController::class, 'index'])->name('songs.index');

        Route::middleware('can:manage-schedule')->group(function () {
            Route::post('/escalas', [ScheduleController::class, 'store'])->name('schedules.store');
            Route::delete('/escalas/{schedule}', [ScheduleController::class, 'destroy'])->name('schedules.destroy');
            Route::post('/escalas/{schedule}/gerar', [ScheduleController::class, 'generate'])->name('schedules.generate');
            Route::post('/escalas/{schedule}/publicar', [ScheduleController::class, 'publish'])->name('schedules.publish');
            Route::post('/escalas/{schedule}/despublicar', [ScheduleController::class, 'unpublish'])->name('schedules.unpublish');
            Route::get('/escalas/{schedule}/mensagens', [ScheduleController::class, 'messages'])->name('schedules.messages');
            Route::put('/cultos/{service}/equipe', [WorshipServiceController::class, 'updateTeam'])->name('services.team');
        });

        Route::middleware('can:manage-repertoire')->group(function () {
            Route::post('/musicas', [SongController::class, 'store'])->name('songs.store');
            Route::put('/musicas/{song}', [SongController::class, 'update'])->name('songs.update');
            Route::delete('/musicas/{song}', [SongController::class, 'destroy'])->name('songs.destroy');
            Route::post('/cultos/{service}/setlist', [SetlistController::class, 'store'])->name('setlist.store');
            Route::put('/cultos/{service}/setlist/{item}', [SetlistController::class, 'update'])->name('setlist.update');
            Route::delete('/cultos/{service}/setlist/{item}', [SetlistController::class, 'destroy'])->name('setlist.destroy');
            Route::post('/cultos/{service}/setlist/{item}/mover', [SetlistController::class, 'move'])->name('setlist.move');
            Route::put('/configuracoes/classificacoes', [SettingsController::class, 'updateCategories'])->name('settings.categories');
        });

        Route::middleware('can:admin')->group(function () {
            Route::get('/membros', [MemberController::class, 'index'])->name('members.index');
            Route::post('/membros', [MemberController::class, 'store'])->name('members.store');
            Route::put('/membros/{member}', [MemberController::class, 'update'])->name('members.update');
            Route::delete('/membros/{member}', [MemberController::class, 'destroy'])->name('members.destroy');

            Route::get('/configuracoes', [SettingsController::class, 'index'])->name('settings.index');
            Route::put('/configuracoes/ministerio', [SettingsController::class, 'updateMinistry'])->name('settings.ministry');
            Route::post('/configuracoes/funcoes', [SettingsController::class, 'storePosition'])->name('settings.positions.store');
            Route::put('/configuracoes/funcoes/{position}', [SettingsController::class, 'updatePosition'])->name('settings.positions.update');
            Route::delete('/configuracoes/funcoes/{position}', [SettingsController::class, 'destroyPosition'])->name('settings.positions.destroy');
        });
    });
});
