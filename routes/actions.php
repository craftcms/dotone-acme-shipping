<?php

declare(strict_types=1);

use Acme\Shipping\Http\Controllers\ShipmentsController;
use Illuminate\Support\Facades\Route;

// Reachable as the action path `acme-shipping/shipments/save`, which is what
// the screen's form posts to.
Route::post('shipments/save', [ShipmentsController::class, 'save']);
