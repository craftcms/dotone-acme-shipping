<?php

declare(strict_types=1);

use Acme\Shipping\Http\Controllers\ShipmentsController;
use Illuminate\Support\Facades\Route;

Route::prefix('acme-shipping')->group(function () {
    Route::get('/', [ShipmentsController::class, 'index'])->name('acme.shipments.index');

    // Ahead of the `{id}` route, which would otherwise swallow it.
    Route::get('catalog', [ShipmentsController::class, 'catalog'])->name('acme.shipments.catalog');

    // One record, three screens — one per rendering approach.
    Route::get('{id}', [ShipmentsController::class, 'edit'])->name('acme.shipments.edit');
    Route::get('{id}/rate', [ShipmentsController::class, 'rate'])->name('acme.shipments.rate');
    Route::get('{id}/legacy', [ShipmentsController::class, 'legacy'])->name('acme.shipments.legacy');
});
