<?php

use App\Http\Controllers\FileController;
use Illuminate\Support\Facades\Route;

Route::post('/files/{objectType}/{objectId}', [FileController::class, 'store']);
Route::delete('/files/{file}', [FileController::class, 'destroy']);
