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
       Route::get('home', 'ManagerController@statisctics');
      
       Route::get('supervisors', 'SupervisorController@supervisors');
       Route::get('supervisors/{supervisor}/profile', 'SupervisorController@supervisorProfile');
       Route::get('supervisor_attendance_statistics', 'SupervisorController@statistics');

       Route::get('sales_reps', 'SalesRepController@getReps');
       Route::get('sales_reps/{rep}/profile', 'SalesRepController@getReps');
       Route::get('salesrep_attendance_statistics', 'SalesRepController@statistics');
       
       Route::get('attendances', 'AttendanceController@index');



    Route::prefix('branches')->group(function () {
        Route::get('/',  'BranchController@index');
        Route::get('/{branch}', 'BranchController@branchDetail');
        Route::get('{branch}/departments', 'BranchController@branchDepartments');
        Route::get('{branch}/products', 'BranchController@branchProducts');

        Route::get('{branch}/departments/{department}', 'DepartmentController@show');

        Route::get('{branch}/departments/{department}/products', 'DepartmentController@departmentProducts');
        Route::get('{branch}/departments/{department}/sales-reps', 'DepartmentController@departmentSalesReps');
    });

   
    Route::prefix('/notifications')->group(function () {
		Route::get('/','NotificationController@notificationListing');
		Route::get('badge-reset','NotificationController@notificationBadgeReset');
	});


     Route::prefix('/plans')->group(function () {
         Route::get('/', 'PlansController@index');
         Route::get('/{plan_id}', 'PlansController@show');
      });

    Route::prefix('/visits')->group(function () {
         Route::get('/', 'VisitsController@index');
         Route::get("/{id}", 'VisitsController@show');
    });

      
});