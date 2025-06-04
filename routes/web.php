<?php

use App\Http\Controllers\UpayPaymentController;
use App\Http\Controllers\panel\internet_user\PanelInternetUserController;
use Illuminate\Support\Facades\Route;

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

Route::get('/', function () {return ['Shadhin Erp' => app()->version()];});
require __DIR__.'/auth.php';

// Route::get('/debug-db', function () {
//     dd(env('DB_USERNAME'), env('DB_PASSWORD'), config('database.connections.mysql.password'));
// });
