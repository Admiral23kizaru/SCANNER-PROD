<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::create(
        '/api/admin/teachers',
        'GET'
    )
);
$request->setUserResolver(function() {
    return \App\Models\User::where('email', 'admin@gmail.com')->first();
});
try {
    $controller = app(\App\Http\Controllers\Api\TeacherManagementController::class);
    $response = $controller->index();
    echo $response->getContent();
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n" . $e->getTraceAsString();
}
