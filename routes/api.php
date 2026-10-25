<?php

use App\Http\Controllers\Api\AccessGateController;
use App\Http\Controllers\Api\PosController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // 1. ئامێرەکانی دەرگای زیرەک (Smart Access Gate Hardware API)
    Route::prefix('access')->group(function () {
        Route::post('/scan', [AccessGateController::class, 'scan'])->name('api.access.scan');
        Route::post('/heartbeat', [AccessGateController::class, 'heartbeat'])->name('api.access.heartbeat');
    });

    // 2. خاڵی فرۆشتنی شۆپی تەواوکەری خۆراکی (POS API)
    Route::prefix('pos')->middleware('tenant')->group(function () {
        Route::get('/shops/{shop}/products', [PosController::class, 'products'])->name('api.pos.products');
        Route::post('/shops/{shop}/checkout', [PosController::class, 'checkout'])->name('api.pos.checkout');
        Route::post('/shops/{shop}/refund', [PosController::class, 'refund'])->name('api.pos.refund');
        Route::post('/shifts/close', [PosController::class, 'closeShift'])->name('api.pos.shift.close');
    });
});
