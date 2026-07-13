<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Http\Request;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});


Route::get('/run-migration', function (Request $request) {
    $migrationFile = '2025_03_22_143622_add_customer_id_to_user_products_table.php';
    
    try {
        Artisan::call('migrate', [
            '--path' => "database/migrations/$migrationFile"
        ]);

        return response()->json([
            'message' => 'Migration executed successfully!',
            'output' => Artisan::output(),
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Error executing migration!',
            'error' => $e->getMessage(),
        ], 500);
    }
});
