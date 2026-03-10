<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
$res = Illuminate\Support\Facades\DB::select('SHOW CREATE TABLE users');
echo $res[0]->{'Create Table'} . "\n";
