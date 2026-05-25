<?php

declare(strict_types=1);

use Givanov95\LaravelAttachments\Controllers\FileController;
use Givanov95\LaravelAttachments\Controllers\ImageController;
use Illuminate\Support\Facades\Route;

Route::middleware(config('attachments.middleware', ['web', 'auth']))->group(function () {
    Route::put('/images/order', [ImageController::class, 'order'])->name('images.order');
    Route::delete('/images/{image}', [ImageController::class, 'destroy'])->name('images.destroy');

    Route::put('/files/order', [FileController::class, 'order'])->name('files.order');
    Route::get('/files/{file}/download', [FileController::class, 'download'])->name('files.download');
    Route::delete('/files/{file}', [FileController::class, 'destroy'])->name('files.destroy');
});
