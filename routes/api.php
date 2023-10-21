<?php

use Illuminate\Http\Request;
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

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::group(['namespace' => 'Auth'], function(){
		Route::post('login', 'LoginController@Authenticate');
		Route::post('logout', 'LoginController@Logout')->middleware('auth:sanctum');
});

//Route::group(['middleware' => ['auth', 'permission']], function() {
Route::group(['middleware' => ['auth:sanctum'],'namespace' => 'Panel'], function(){
   	Route::resource('users', UserController::class,['except' => ['edit', 'create']]);
	Route::get('myprofile', 'UserController@myProfile');   
	Route::resource('roles', RoleController::class,['except' => ['edit', 'create']]);
    Route::get('permissions', 'RoleController@allPermissions');
});


