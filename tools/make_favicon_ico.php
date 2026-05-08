<?php
declare(strict_types=1);

/**
 * Create a minimal favicon.ico that embeds a PNG image entry.
 *
 * Many browsers accept ICO files that contain PNG payloads.
 * Source: public/favicon-32x32.png
 * Output: public/favicon.ico
 */

$root = dirname(__DIR__);
$src  = $root . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'favicon-32x32.png';
$out  = $root . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'favicon.ico';

if (!is_file($src)) {
    fwrite(STDERR, "Source not found: {$src}\n");
    exit(1);
}

$png = file_get_contents($src);
if ($png === false || $png === '') {
    fwrite(STDERR, "Failed to read: {$src}\n");
    exit(1);
}

// ICO header: reserved(0), type(1=icon), count(1)
$header = pack('vvv', 0, 1, 1);

// Directory entry (16 bytes):
// width, height, colorCount, reserved, planes, bitCount, bytesInRes, imageOffset
$width  = 32;
$height = 32;
$bytes  = strlen($png);
$offset = 6 + 16;

$dir = pack(
    'CCCCvvVV',
    $width,      // width (0 means 256, but we use 32)
    $height,     // height
    0,           // palette colors
    0,           // reserved
    1,           // planes
    32,          // bit count (nominal)
    $bytes,      // bytes in resource
    $offset      // offset to image data
);

if (file_put_contents($out, $header . $dir . $png) === false) {
    fwrite(STDERR, "Failed to write: {$out}\n");
    exit(1);
}

fwrite(STDOUT, "Wrote {$out}\n");

