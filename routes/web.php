<?php

use App\Http\Controllers\CredentialController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\WorkflowController;
use App\Http\Controllers\WorkflowExecutionController;
use App\Http\Controllers\WorkflowGraphController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WorkflowMemoryController;
use App\Http\Controllers\WorkflowPublishController;

Route::get('/', [HomeController::class, 'index'])
    ->name('home');

Route::middleware('guest')->group(function () {
    Route::view('/login', 'pages.auth.login')->name('login');
    Route::view('/register', 'pages.auth.register')->name('register');
    Route::view('/forgot-password', 'pages.auth.forgot-password')
        ->name('password.request');
});

Route::middleware('auth')
    ->prefix('app')
    ->name('app.')
    ->group(function () {
        Route::get('/workflows', [WorkflowController::class, 'index'])
            ->name('workflows.index');
        Route::get('/workflows/{workflow:public_id}', [WorkflowController::class, 'show'])
            ->name('workflows.show');
        Route::prefix('api')->name('api.')->group(function () {
            Route::get('ping', fn () => response()->json(['status' => 'ok']))->name('ping');
            Route::get('workflows/{workflow}/graph', [WorkflowGraphController::class, 'show'])
                ->name('workflows.graph');
            Route::put('workflows/{workflow}/graph', [WorkflowGraphController::class, 'update'])
                ->middleware('throttle:90,1')
                ->name('workflows.graph.update');
            Route::post('workflows/{workflow}/run', [WorkflowExecutionController::class, 'run'])
                ->middleware('throttle:30,1')
                ->name('workflows.run');
            Route::post('workflows/{workflow}/cancel', [WorkflowExecutionController::class, 'cancel'])
                ->name('workflows.cancel');
            Route::post('workflows/{workflow}/publish', [WorkflowPublishController::class, 'publish'])
                ->middleware('throttle:20,1')
                ->name('workflows.publish');
            Route::post('workflows/{workflow}/unpublish', [WorkflowPublishController::class, 'unpublish'])
                ->middleware('throttle:20,1')
                ->name('workflows.unpublish');
            Route::post('workflows/{workflow}/memory/clear', [WorkflowMemoryController::class, 'clear'])
                ->middleware('throttle:30,1')
                ->name('workflows.memory.clear');
            Route::post('workflows/{workflow}/nodes/{node}/run', [WorkflowExecutionController::class, 'runNode'])
                ->middleware('throttle:60,1')
                ->name('workflows.nodes.run');
            Route::patch('workflows/{workflow}', [WorkflowController::class, 'rename'])
                ->middleware('throttle:60,1')
                ->name('workflows.rename');
            Route::apiResource('credentials', CredentialController::class);
            Route::get('credentials/{credential}/models', [CredentialController::class, 'models'])->name('credentials.models');
        });
    });

Route::post('/logout', function () {
    auth()->logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect('/');
})->middleware('auth')->name('logout');
