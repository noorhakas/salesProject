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
		Route::post('sendOtpCode','ForgetPasswordController@SendEmail');
		Route::post('check_otp_code','ForgetPasswordController@checkOtpCode');
		Route::post('reset_password','ForgetPasswordController@ResetPassword');
		Route::post('logout', 'LoginController@Logout')->middleware('auth:sanctum');
});

Route::group(['middleware' => ['auth:sanctum'],'namespace' => 'Panel'], function(){
	Route::get('myprofile', 'UserController@myProfile')->name('users.profile'); 
    Route::post('update_profile', 'UserController@updateProfile'); 
	Route::get('current_plan', 'UserController@MycurrentPlan');   
	
 

	/** specialty && products*/
	/*Route::post('add_product_note', 'ProductController@addNotes'); 
	Route::get('product_notes/{id}','ProductController@getProductNotes');
    Route::get('product_file/{id}','ProductController@getProductFiles');*/

});

	Route::get('position_list', 'Panel\PositionController@index');

 




