<?php

use Yource\ExactOnlineClient\Http\Controllers\ExactOnlineConnectController;

Route::prefix('exact-online')
    ->middleware(config('laravel-exact-online.exact_multi_user') ? ['web', 'auth'] : ['web'])
    ->name('exact-online.')
    ->group(function () {
        Route::get(
            'connect',
            [ExactOnlineConnectController::class, 'connect']
        )->name('connect');

        Route::post(
            'authorize',
            [ExactOnlineConnectController::class, 'authorize']
        )->name('authorize');

        Route::get(
            'oauth',
            [ExactOnlineConnectController::class, 'callback']
        )->name('callback');
    });
