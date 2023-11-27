<?php

use Illuminate\Http\Request;
use App\Http\Controllers\MessageController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
Route::get('/send-message',[MessageController::class,'sendMessages']);
Route::get('/whatsapp-webhook',[MessageController::class,'verifywebhook']);
Route::post('/whatsapp-webhook',[MessageController::class,'processWebhook']);
Route::get('/messages', [MessageController::class, 'index']);
Route::post('/messages', [MessageController::class, 'store']);
Route::get('/messages/{message}', [MessageController::class, 'show']);
Route::apiResources([
    'messages' => MessageController::class,
]);
