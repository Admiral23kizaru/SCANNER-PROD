<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;
$user = User::where('role_id', 1)->first();
if ($user) {
    $user->email = 'admin@gmail.com';
    $user->password = Hash::make('admin123');
    $user->save();
    echo "Admin reset successful.\n";
} else {
    echo "Admin not found.\n";
}
