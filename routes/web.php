<?php

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/webhook', function () {
    $token = env('TELEGRAM_BOT_TOKEN');
    $webhookUrl = 'https://testt.shamcrm.com/api/telegram/webhook';

    $response = Http::get("https://api.telegram.org/bot{$token}/setWebhook", [
        'url' => $webhookUrl,
    ]);

    return $response->json();
});

Route::get('/deleteWebhook', function () {
    $token = env('TELEGRAM_BOT_TOKEN');

    $response = Http::get("https://api.telegram.org/bot{$token}/deleteWebhook");

    return $response->json();
});
