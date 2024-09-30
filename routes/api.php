<?php

use App\Http\Controllers\auth\UserController;
use App\Http\Controllers\Api\EngineerController;
use App\Models\InfoBipModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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



Route::get("test-bip-emai",function(){
    InfoBipModel::SendEmail("rehan5383@gmail.com","<h1>Hello world</h1>","Test Email");
});
