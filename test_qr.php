<?php
require 'vendor/autoload.php';
try {
    $employeeId = '232323';
    $qrCode = new \Endroid\QrCode\QrCode(data: $employeeId, size: 200, margin: 0);
    $writer = new \Endroid\QrCode\Writer\PngWriter();
    $result = $writer->write($qrCode);
    echo $result->getDataUri();
} catch (\Throwable $e) {
    echo $e->getMessage() . "\n" . $e->getTraceAsString();
}
