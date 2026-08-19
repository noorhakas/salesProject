<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\Panel\Manager\AttendanceController;


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
       Route::get('home', 'ManagerController@statisctics');
      
       Route::get('supervisors', 'SupervisorController@supervisors');
       Route::get('supervisors/{supervisor}/profile', 'SupervisorController@supervisorProfile');
       Route::get('supervisors/{supervisor}/salesrep', 'SupervisorController@supervisorSalesPeo');
       
       Route::get('supervisor_attendance_statistics', 'SupervisorController@statistics');

       Route::get('sales_reps', 'SalesRepController@getReps');
       Route::get('sales_reps/{sales_rep}/profile', 'SalesRepController@profile');
       Route::get('salesrep_attendance_statistics', 'SalesRepController@statistics');
       
      /**  accounts */ 
       Route::get('accounts', 'AccountsController@index');
       Route::get('accounts/{id}', 'AccountsController@showAccount');
       Route::get('customers', 'CustomersController@index');
       Route::get('customers/{id}', 'CustomersController@showCustomer');

      
      Route::get('/departments', 'DepartmentController@myDepartments');


    Route::prefix('branches')->group(function () {
        Route::get('/',  'BranchController@index');
        Route::get('/{branch}', 'BranchController@branchDetail');
        Route::get('{branch}/departments', 'BranchController@branchDepartments');
        Route::get('{branch}/products', 'BranchController@branchProducts');

        Route::get('{branch}/departments/{department}', 'DepartmentController@show');

        Route::get('{branch}/departments/{department}/products', 'DepartmentController@departmentProducts');
        Route::get('{branch}/departments/{department}/sales-reps', 'DepartmentController@departmentSalesReps');
    });

    Route::get('products/{product}', 'ProductController@show');

    Route::prefix('/notifications')->group(function () {
		Route::get('/','NotificationController@notificationListing');
		Route::get('badge-reset','NotificationController@notificationBadgeReset');
	});


    Route::prefix('/plans')->group(function () {
            Route::get('/', 'PlansController@index');
            Route::get('/{plan_id}', 'PlansController@show');
            Route::post('/accept', 'PlansController@accept');
            Route::post('/reject', 'PlansController@reject');
    });

    Route::prefix('/visits')->group(function () {
         Route::get('/', 'VisitsController@index');
         Route::get("/{id}", 'VisitsController@show');
    });

      Route::get('attendances', [AttendanceController::class,'index']);
       Route::post('attendance',[AttendanceController::class,'storeAttendance'])->name('manager.attendance');
       Route::get('attendance-today-status',[AttendanceController::class,'todayAttendanceStatus'])->name('manager.attendance-today-status');
       Route::get('attendance_log',[AttendanceController::class,'getAttendanceLog'])->name('manager.attendance_log');
 


      
});