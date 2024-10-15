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

Route::get("available_engineer_after_interval",[UserController::class,"getAvailableEngineers"]);

// Route::get("test-bip-emai",function(){
//     InfoBipModel::SendEmail("rehan5383@gmail.com","<h1>Hello world</h1>","Test Email");
// });

Route::group(["prefix" => "/engineers"],function(){
    Route::get("/getJobTypes",[EngineerController::class,"AllJopTypes"]);
    Route::get("{id}/detail",[EngineerController::class,"engineerDetail"]);
    Route::post("/signIn",[EngineerController::class,"signin"]);
    Route::post("/{id}/update",[EngineerController::class,"updateEngineer"]);
    Route::post("/addAvailability",[EngineerController::class,"AddAvailability"]);
    Route::post("/{id}/updateAvailability",[EngineerController::class,"UpdateAvailability"]);
    Route::get("/{id}/deleteAvailability",[EngineerController::class,"DeleteAvailability"]);
    Route::post("/addLatLong",[EngineerController::class,"AddLatLong"]);
    Route::get("/{id}/deleteEngineer",[EngineerController::class,"deleteEngineer"]);
    Route::post("/signUp",[EngineerController::class,"signUp"]);
    Route::post("/{id}/editProfile",[EngineerController::class,"EditProfile"]);
    Route::post("/{id}/changePassword",[EngineerController::class,"ChangePassword"]);
    Route::post("/forgetPassword",[EngineerController::class,"ForgetPassword"]);
});
