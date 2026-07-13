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


Route::group(['middleware' => ['auth:sanctum']], function(){


   Route::get('customers', 'CustomerController@index');
   Route::get('customers/{id}', 'CustomerController@show');

   Route::get('accounts', 'AccountController@index');
   Route::get('accounts/{id}', 'AccountController@show');

      Route::prefix('/plans')->group(function () {
         Route::get('/', 'PlansController@index');
         Route::post('/','PlansController@store');
         Route::delete('/{plan}','PlansController@destroy');
         Route::get('detail', 'PlansController@planDetail');
      });

      Route::prefix('/visits')->group(function () {
         Route::post('/', 'VisitsController@index');
         Route::get('current_visits', 'VisitsController@currentVisits');
         Route::get("/{id}", 'VisitsController@show');
         Route::post('create_unplanned_visit','VisitsController@createUnplannedVisit');
         Route::post('savevisit','VisitsController@store');
      });

      Route::prefix('/notifications')->group(function () {
            Route::get('/','NotificationController@notificationListing');
            Route::get('badge-reset','NotificationController@notificationBadgeReset');
      });
});