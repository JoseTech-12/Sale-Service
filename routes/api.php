<?php

use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SaleController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
Route::get('/sales', [SaleController::class, 'index']);
Route::post('/createSale', [SaleController::class, 'store']);
Route::put('/updateSale/{id}', [SaleController::class, 'update']);
Route::delete('/deleteSale/{id}', [SaleController::class, 'destroy']);
Route::get('/showSales/{id}', [SaleController::class, 'show']);
