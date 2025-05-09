<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UploadController;


Route::get('/', function () {
    return view('welcome');
});
Route::get('/', [UploadController::class, 'index'])->name('uploads.index');
Route::post('/upload', [UploadController::class, 'store'])->name('uploads.store');
