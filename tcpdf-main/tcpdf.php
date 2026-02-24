<?php

declare(strict_types=1);

// Compatibility shim: older code expects `base_path('tcpdf-main/tcpdf.php')`.
// TCPDF is installed via Composer (tecnickcom/tcpdf).

require_once __DIR__ . '/../vendor/autoload.php';

if (!class_exists(\TCPDF::class)) {
    $vendorTcpdf = __DIR__ . '/../vendor/tecnickcom/tcpdf/tcpdf.php';
    if (is_file($vendorTcpdf)) {
        require_once $vendorTcpdf;
    }
}

