<?php

use Illuminate\Support\Facades\Route;
use RichardRoman\ShortLinks\Laravel\Http\Controllers\QrController;
use RichardRoman\ShortLinks\Laravel\Http\Controllers\RedirectController;

$routePrefix = (string) config('short-links.route_prefix', 'l');
$throttle = (string) config('short-links.throttle', '120,1');
$codigoPattern = (string) config('short-links.route_pattern', '[a-hjkmnp-z2-9]{8}');

Route::middleware(['web', 'throttle:' . $throttle])->group(function () use ($routePrefix, $codigoPattern): void {
    Route::get($routePrefix . '/{codigo}/qr', QrController::class)
        ->where('codigo', $codigoPattern)
        ->name('short-links.qr');

    Route::get($routePrefix . '/{codigo}', RedirectController::class)
        ->where('codigo', $codigoPattern)
        ->name('short-links.redirect');
});
