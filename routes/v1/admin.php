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
   

   ###users
    Route::resource('admins', 'AdminController')->except(['edit', 'create']);
    Route::resource('users', 'UserController')->except(['edit', 'create']);
    Route::resource('roles', 'RoleController')->except(['edit', 'create']);;
    Route::get('permissions', 'RoleController@allPermissions');
	Route::apiResource('managers', 'ManagerController');

   
   ###setting
   Route::resource('acc_type', 'AccTypeController')->except(['edit', 'create']);
   Route::resource('classes', 'ClassesController')->except(['edit', 'create']);
   Route::resource('bricks', 'BricksController')->except(['edit', 'create']);
   Route::resource('positions', 'PositionController')->except(['edit', 'create']);
   Route::resource('departments', 'DepartmentController')->except(['edit', 'create']);
   Route::resource('branches', 'BranchController')->except(['edit', 'create']);
   Route::resource('specialty', 'SpecialtyController')->except(['edit', 'create']);



   ##plans
	 Route::prefix('/plans')->group(function () {
		Route::post('accept_plan','PlansController@AcceptPlan');
		Route::get('detail', 'PlansController@planDetail');
		Route::post('reject_plan','PlansController@RejectPlan');
		Route::delete('/{plan}','PlansController@destroy');
        Route::get('all_plans', 'PlansController@getAllPlans');
	});


   ###users
  

   Route::get('fetch_accounts_customers','CustomerController@FetchAccountAndCustomers');
   Route::get('export_user_list/{id}','CustomerController@exportUserAccounts');


   Route::get('myprofile', 'UserController@myProfile')->name('users.profile'); 
   Route::post('update_profile', 'UserController@updateProfile'); 

   ##customer
    Route::resource('customers', 'CustomerController')->except(['edit', 'create']);
	Route::resource('accounts', 'AccountController')->except(['edit', 'create']);


	### products
	Route::resource('products', 'ProductController')->except(['edit', 'create']);
	Route::resource('company', 'CompanyController')->except(['edit', 'create']);
	Route::resource('category', 'CategoryController')->except(['edit', 'create']);

    ##home&settings
	Route::get('dashboard-stats', 'HomeController@index');
	Route::get('logs', 'HomeController@getLogs');
	Route::post('maps', 'MapController@getMaps');
    Route::resource('setting', 'SettingController')->only(['index', 'store']);
  

   ###attendance
	Route::get('attendance/daily-summary', 'AttendanceController@dailySummary');
   


	###visits
	Route::prefix('/visits')->group(function () {
		Route::post('/', 'VisitsController@index');
		Route::get('/schedule', 'VisitsController@VisitAsSchedule');
		Route::get('/all_visits', 'VisitsController@AllVisits');
        Route::get('current_visits', 'VisitsController@currentVisits');
        Route::post('all_user_visit','VisitsController@UserVisits');
		Route::post('whole_user_visits','VisitsController@getAllUserVisits');
		Route::get("/{id}", 'VisitsController@show');
		Route::post('visit-charts', 'VisitsController@visitCharts');
		Route::post('user-visit-statictics', 'VisitsController@userVisitStatictics');
        Route::post('user-visit-sales-statictics', 'VisitsController@userVisitSalesStatictics');
	});

	Route::get('position_list', 'PositionController@index');

	
 
	/** specialty && products*/
	Route::resource('products', 'ProductController')->except(['edit', 'create']);
	Route::post('add_product_note', 'ProductController@addNotes'); 
	Route::get('product_notes/{id}','ProductController@getProductNotes');
    Route::get('product_file/{id}','ProductController@getProductFiles');
	Route::resource('company', 'CompanyController')->except(['edit', 'create']);
	
	
	Route::resource('category', 'CategoryController')->except(['edit', 'create']);
	Route::resource('gifts', 'GiftController')->except(['edit', 'create']);
       

     Route::prefix('/notifications')->group(function () {
		Route::get('/','NotificationController@notificationListing');
		Route::get('badge-reset','NotificationController@notificationBadgeReset');
	});
	

        Route::prefix('/sales')->group(function () {
		Route::post('save_user_sales', 'SalesController@storeUserSales');
		Route::get('user_product_sales/{account_id}', 'SalesController@getUserProductSales');
		Route::get('product_account_sales', 'SalesController@getProductAccountsSales');

		
	});



	

	Route::prefix('/notifications')->group(function () {
		Route::get('/','NotificationController@notificationListing');
		Route::get('badge-reset','NotificationController@notificationBadgeReset');
	});


	
 
/*========================export&&import==================================*/
		Route::prefix('/export')->group(function () {
			Route::get('doctors','CustomerController@exportDoctors');
			Route::get('pharmacy','AccountController@exportPharmacy');
			Route::get('accounts','AccountController@exportAccounts');
			Route::get('products','ProductController@exportProducts');
			Route::get('user-doctor-visits', 'VisitsController@exportUserVisitsToExcel');

		});

		Route::prefix('/import')->group(function () {
			Route::post('doctors','CustomerController@importDoctors');
			Route::post('pharmacy','AccountController@importPharmacy');
			Route::post('accounts','AccountController@importAccounts');
			Route::post('products','ProductController@importProducts');
                        Route::post('useraccount','AccountController@importUserAccounts');
                        Route::post('user_list','UserController@importUserList');

		});
Route::get('account-chart', 'AccountController@accountChart');
Route::get('doctor-chart', 'CustomerController@doctorChart');

	
});
 




