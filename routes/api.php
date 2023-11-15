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

Route::group(['middleware' => ['auth:sanctum'],'namespace' => 'Panel'], function(){
   	Route::resource('users', 'UserController')->except(['edit', 'create']);;
	Route::get('myprofile', 'UserController@myProfile')->name('users.profile'); 
    Route::post('update_profile', 'UserController@updateProfile'); 
	Route::get('current_plan', 'UserController@MycurrentPlan');   
	Route::resource('roles', 'RoleController')->except(['edit', 'create']);;
	Route::get('permissions', 'RoleController@allPermissions');
	Route::get('position_list', 'UserController@getPositionList');
	

	/** specialty && products*/
	Route::resource('specialty', 'SpecialtyController')->except(['edit', 'create']);
	Route::resource('products', 'ProductController')->except(['edit', 'create']);
	Route::resource('classes', 'ClassesController')->except(['edit', 'create']);
	Route::resource('bricks', 'BricksController')->except(['edit', 'create']);
	Route::resource('acc_type', 'AccTypeController')->except(['edit', 'create']);
	Route::resource('customers', 'CustomerController')->except(['edit', 'create']);
	Route::resource('accounts', 'AccountController')->except(['edit', 'create']);
	Route::resource('gifts', 'GiftController')->except(['edit', 'create']);



	Route::prefix('/plans')->group(function () {
		Route::get('/', 'PlansController@index');
		Route::post('/','PlansController@store');
		Route::get('detail', 'PlansController@planDetail');
        Route::get('all_plans', 'PlansController@getAllPlans');
		
	});


	Route::prefix('/visits')->group(function () {
		Route::post('/', 'VisitsController@index');
		Route::get('/schedule', 'VisitsController@VisitAsSchedule');
		Route::get('/all_visits', 'VisitsController@AllVisits');
        Route::post('all_user_visit','VisitsController@UserVisits');	
		Route::get("/{id}", 'VisitsController@show');
		Route::post('savevisit','VisitsController@store');
		Route::post('visit-charts', 'VisitsController@visitCharts');
	});

	Route::prefix('/notifications')->group(function () {
		Route::get('/','NotificationController@notificationListing');
		Route::get('badge-reset','NotificationController@notificationBadgeReset');
	});


	Route::get('dashboard-stats', 'HomeController@index');
	Route::get('logs', 'HomeController@getLogs');
	Route::post('maps', 'MapController@getMaps');
	


});


