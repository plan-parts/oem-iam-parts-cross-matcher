<?php

use Illuminate\Support\Facades\Route;
use PlanParts\CrossMatcher\Http\Controllers\CrossMatcherController;

Route::group([
    'prefix'     => config('plan-parts.route_prefix', 'cross-matcher'),
    'middleware' => config('plan-parts.middleware', ['web']),
    'as'         => 'plan-parts.',
], function () {
    Route::get('/', [CrossMatcherController::class, 'index'])->name('index');
    Route::post('/upload', [CrossMatcherController::class, 'upload'])->name('upload');
    Route::delete('/price-lists/{id}', [CrossMatcherController::class, 'destroy'])->name('price-lists.destroy');
    Route::get('/suggest', [CrossMatcherController::class, 'suggest'])->name('suggest');
});
