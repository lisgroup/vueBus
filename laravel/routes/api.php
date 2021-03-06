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

Route::group(['prefix' => 'user'], function($router) {
    Route::any('startCaptcha', 'AuthController@startCaptcha'); // 获取-极验验证码
    Route::any('verifyCaptcha', 'AuthController@verifyCaptcha'); // 校验-极验验证码
    Route::any('login', 'AuthController@login')->name('login'); // ->middleware('admin.login');
    Route::any('logout', 'AuthController@logout');
    Route::any('refresh', 'AuthController@refresh');
    Route::any('info', 'AuthController@info');
});

Route::resource('crontask', 'Api\CronTaskController');
Route::resource('lines', 'Api\LinesController');
Route::resource('user', 'Api\UserController');

/************************* 车次任务相关操作 start ************************/
Route::any('bus_line_search', 'Api\LinesController@busLineSearch');
Route::post('postCrontask', 'Api\CronTaskController@postCrontask');
Route::any('bus_line_list', 'Api\LinesController@busLineList');
/************************* 车次任务相关操作 end  ************************/
// Route::any('table/list', 'Api\CronTaskController@list');

Route::any('line_search', 'Api\LinesController@search');
Route::any('user_password', 'Api\UserController@password');
Route::any('clearCache', 'Api\LinesController@clearCache');
