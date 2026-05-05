<?php

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

// Simulate the /qrid/ path for local development (php artisan serve)
// This captures requests to /qrid/images/*, /qrid/logo/*, /qrid/storage/*
// and serves the actual files from the public directory so that ASSET_URL=/qrid works locally!
if (app()->environment('local')) {
    Route::get('/qrid/{path}', function ($path) {
        // Only serve static assets (images, storage, etc)
        $fullPath = public_path($path);
        if (file_exists($fullPath) && is_file($fullPath)) {
            $extension = pathinfo($fullPath, PATHINFO_EXTENSION);
            
            // Basic mime types mapping to prevent browsers blocking the asset
            $mimeTypes = [
                'png' => 'image/png',
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'svg' => 'image/svg+xml',
                'css' => 'text/css',
                'js' => 'application/javascript',
            ];
            
            $headers = [];
            if (isset($mimeTypes[strtolower($extension)])) {
                $headers['Content-Type'] = $mimeTypes[strtolower($extension)];
            }
            
            return response()->file($fullPath, $headers);
        }
        // If file not found, let it proceed to standard 404
        abort(404);
    })->where('path', '.*');
}

// Explicit SPA entry points for subdirectory hosting stability.
Route::view('/', 'app');
Route::view('/login', 'app');
Route::view('/scanner', 'app');

Route::get('/{any}', function () {
    return view('app');
})->where('any', '.*');
