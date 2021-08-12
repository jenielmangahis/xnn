<?php

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

// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::prefix('auth')->namespace('Api')->group(function() {
    Route::post('token', 'AccessTokenController@issueToken');
    Route::post('refresh', 'AccessTokenController@refreshToken');
    Route::get('user', 'AccessTokenController@user')->middleware('jwt.auth');
});