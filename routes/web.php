<?php

use App\Http\Controllers\auth\UserController;
use App\Http\Controllers\EngineerController;
use App\Http\Controllers\JobController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/
Route::get('/mails-check1', [JobController::class, 'listEmails1'])->name('mails.check1');

Route::get('/', [UserController::class,"index"]);
Route::post('/', [UserController::class,"processAuthRequest"]);
Route::get("logout",[UserController::class,"logout"]);

Route::get('/login/google', [UserController::class, 'redirectToGoogle'])->name('login.google');
Route::get('/callback', [UserController::class, 'handleGoogleCallback']);
Route::get('/mails-check', [JobController::class, 'listEmails'])->name('mails.check');
Route::get('/gmail-login-check', [JobController::class, 'GmailCheck'])->name('gmail.login.check');
Route::get('/gmail-error-check', [JobController::class, 'GmailErrorCheck'])->name('gmail.error.check');

Route::resource('/engineers',EngineerController::class);
Route::resource('/jobs',JobController::class);
Route::get('/jobs/assign_engineer/{id}',[JobController::class, 'AssignEngineer'])->name('job.assign_engineer');
Route::post('/jobs/assign_engineer/{id}',[JobController::class, 'AssignEngineerPost'])->name('job.assign_engineer.post');
Route::get('/jobs/assign_agent/{id}',[JobController::class, 'AssignAgent'])->name('job.assign_agent');
Route::post('/jobs/assign_agent/{id}',[JobController::class, 'AssignAgentPost'])->name('job.assign_agent.post');
Route::get('/jobs/assign_hand_over/{id}',[JobController::class, 'AssignHandover'])->name('job.assign_hand_over');
Route::post('/jobs/assign_hand_over/{id}',[JobController::class, 'AssignHandoverPost'])->name('job.assign_hand_over.post');
Route::get('dashboard/latest_data',[JobController::class,"latestData"]);

Route::get("tv-view",[UserController::class,"ShowTvView"]);
Route::get('tv_dashboard/latest_data',[JobController::class,"latestTvData"]);
Route::resource("users",\App\Http\Controllers\UserController::class);

