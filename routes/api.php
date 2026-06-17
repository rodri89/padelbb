<?php

use App\Http\Controllers\Api\MenuApiController;
use Illuminate\Http\Request;

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

Route::group(['prefix' => 'auth'], function () {
    Route::post('login', 'AuthController@login');
    Route::post('signup', 'AuthController@signup');
  
    Route::group(['middleware' => 'auth:api'], function() {
        Route::get('logout', 'AuthController@logout');
        Route::get('user', 'AuthController@user');        
    });
});

Route::group(['middleware' => 'auth:api'], function () {
    Route::post('/mobile/devices/register', 'Api\MobileDeviceController@register');
    Route::post('/mobile/devices/unregister', 'Api\MobileDeviceController@unregister');
    Route::post('/mobile/firebase-token', 'Api\MobileFirebaseTokenController@store');

    Route::get('/chats/threads', 'Api\ChatThreadController@index');
    Route::post('/chats/threads', 'Api\ChatThreadController@store');
    Route::post('/chats/threads/{threadId}/messages', 'Api\ChatMessageController@store');
});

Route::get('/menu', [MenuApiController::class, 'index']);
Route::get('/menu/{menuItem}', [MenuApiController::class, 'show']);