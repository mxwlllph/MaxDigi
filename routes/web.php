<?php

use Illuminate\Support\Facades\Route;
use Mxwlllph\MaxDigi\Http\Controllers\WebhookController;

// Route untuk menerima callback/webhook dari DigiFlazz
Route::post('webhook', [WebhookController::class, 'handle'])->name('maxdigi.webhook');
