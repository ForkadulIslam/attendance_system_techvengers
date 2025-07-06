<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

Route::group(['middleware' => 'guest'], function () {
    Route::get('/', function () {
        $data['menu'] = 'Home';
        return View::make('loginPage', $data)->render();
    });
});

Route::controller('login', 'LoginController');

Route::group(['middleware' => 'auth'], function () {
    Route::controller('user', 'UserController');
    Route::get('break-start', 'BreakTimeController@breakStart')->name('break.start');
    Route::get('break-end', 'BreakTimeController@breakEnd')->name('break.end');
});

Route::group(['middleware' => 'auth.company'], function () {
    Route::get('company/notice-board/create', 'CompanyController@getNoticeBoardCreate');
    Route::get('company/notice-board/{id}/edit', 'CompanyController@getNoticeBoardEdit');
    Route::get('company/designation/{id}/edit', 'CompanyController@getDesignationEdit');
    Route::get('company/all-user/{id}/force', 'CompanyController@getForce');
    Route::post('company/all-user/{id}/force', 'CompanyController@postForce');
    Route::controller('company', 'CompanyController');
});

Route::get('logs', '\Rap2hpoutre\LaravelLogViewer\LogViewerController@index');


//API routes
Route::post('api/break-start', 'ApiController@breakStart');
Route::post('api/break-end', 'ApiController@breakEnd');
Route::post('api/punch-in', 'ApiController@punchIn');
Route::post('api/punch-out', 'ApiController@punchOut');
Route::post('api/login', 'ApiController@login');
Route::get('api/userStatus', 'ApiController@userStatus');
Route::post('api/screenshot-upload', 'ApiController@screenshotUpload');
Route::post('api/idle-time', 'ApiController@idleTimeStore');
Route::get('api/getUser/{id}', 'ApiController@getUser');