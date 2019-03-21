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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

// before
Auth::routes();

// Api Version 1
Route::group([
    'prefix' => 'v1/auth/',
], function () {

    // Account
    Route::post('login', 'AuthController@login');
    Route::post('signup', 'AuthController@signup');
    Route::post('password/reset', 'AuthController@reset');
    Route::post('create-password-reset', 'AuthController@create');
    Route::get('signup/activate/{token}', 'AuthController@signupActivate');
    Route::get('password/find/{token}', 'AuthController@find');

    // Files
    Route::resource('files', 'FileController');

    Route::group([
        'middleware' => 'auth:api',
    ], function () {
        Route::get('logout', 'AuthController@logout');
        Route::get('user', 'AuthController@user');
    });
});
