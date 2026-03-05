<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\StudentController;

Route::get('/verify/{student_number}', [StudentController::class, 'verify'])
    ->name('students.verify');

Route::get('/guard', function () {
    return view('guard');
});

Route::get('/{any}', function () {
    return view('app');
})->where('any', '.*');
