<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);

// Create a token for user 1
$user = \App\Models\User::find(1);
$token = $user->createToken('debug')->plainTextToken;

$req = \Illuminate\Http\Request::create('/api/admin/teachers', 'GET');
$req->headers->set('Accept', 'application/json');
$req->headers->set('Authorization', 'Bearer ' . $token);

$resp = $kernel->handle($req);
echo "STATUS: " . $resp->getStatusCode() . "\n";
echo $resp->getContent() . "\n";
