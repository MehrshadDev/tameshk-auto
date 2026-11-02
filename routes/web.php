<?php

use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\CredentialController;
use App\Http\Controllers\GoogleSheetsOptionsController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OAuth\GoogleOAuthController;
use App\Http\Controllers\WorkflowController;
use App\Http\Controllers\WorkflowExecutionController;
use App\Http\Controllers\WorkflowGraphController;
use App\Http\Controllers\WorkflowMemoryController;
use App\Http\Controllers\WorkflowPublishController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;

Route::get('/', [HomeController::class, 'index'])
    ->name('home');

Route::get('/oauth/google/callback', [GoogleOAuthController::class, 'callback'])
    ->name('oauth.google.callback');

Route::middleware('guest')->group(function () {
    Route::view('/login', 'pages.auth.login')->name('login');
    Route::view('/register', 'pages.auth.register')->name('register');
    Route::view('/forgot-password', 'pages.auth.forgot-password')
        ->name('password.request');

    Route::get('/auth/google/redirect', [\App\Http\Controllers\Auth\GoogleAuthController::class, 'redirect'])
        ->middleware('throttle:20,1')
        ->name('auth.google.redirect');

    Route::get('/auth/google/callback', [\App\Http\Controllers\Auth\GoogleAuthController::class, 'callback'])
        ->middleware('throttle:20,1')
        ->name('auth.google.callback');

    Route::post('/auth/google/one-tap', [\App\Http\Controllers\Auth\GoogleAuthController::class, 'oneTap'])
        ->middleware('throttle:15,1')
        ->name('auth.google.one-tap');
});

Route::middleware('auth')
    ->prefix('app')
    ->name('app.')
    ->group(function () {
        Route::get('/workflows', [WorkflowController::class, 'index'])
            ->name('workflows.index');
        Route::get('/workflows/{workflow:public_id}', [WorkflowController::class, 'show'])
            ->name('workflows.show');
        Route::view('/runs', 'pages.runs.index')
            ->name('runs.index');
        Route::view('/credentials', 'pages.credentials.index')
            ->name('credentials.index');

        Route::prefix('api')->name('api.')->group(function () {
            Route::get('ping', fn () => response()->json(['status' => 'ok']))->name('ping');

            Route::patch('profile', function (Request $request) {
                $data = $request->validate([
                    'name' => ['required', 'string', 'min:2', 'max:50'],
                ]);

                $cleanName = trim(strip_tags($data['name']));

                if (mb_strlen($cleanName) < 2) {
                    throw ValidationException::withMessages(['name' => 'نام واردشده معتبر نیست.']);
                }

                $request->user()->update(['name' => $cleanName]);

                return response()->json(['name' => $cleanName]);
            })->middleware('throttle:20,1')->name('profile.update');

            Route::get('oauth/google/redirect', [GoogleOAuthController::class, 'redirect'])
                ->name('oauth.google.redirect');

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

            Route::get('credentials/{credential}/google/spreadsheets', [GoogleSheetsOptionsController::class, 'spreadsheets'])
                ->middleware('throttle:60,1')
                ->name('credentials.google.spreadsheets');
            Route::get('credentials/{credential}/google/spreadsheets/{spreadsheet}/sheets', [GoogleSheetsOptionsController::class, 'sheets'])
                ->middleware('throttle:60,1')
                ->name('credentials.google.sheets');

            Route::apiResource('credentials', CredentialController::class);
            Route::get('credentials/{credential}/models', [CredentialController::class, 'models'])
                ->name('credentials.models');
        });
    });

Route::post('/logout', function () {
    auth()->logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect('/');
})->middleware('auth')->name('logout');
