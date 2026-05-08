<?php
declare(strict_types=1);

/**
 * Generate favicon assets from public/scanup.png.
 *
 * Output:
 * - public/favicon-16x16.png
 * - public/favicon-32x32.png
 * - public/favicon-48x48.png
 * - public/apple-touch-icon.png (180x180)
 *
 * Requirements: PHP GD with PNG/JPEG support.
 */

$root = dirname(__DIR__);
$src  = $root . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'scanup.png';

if (!is_file($src)) {
    fwrite(STDERR, "Source not found: {$src}\n");
    exit(1);
}

if (!function_exists('exif_imagetype') || !function_exists('imagesavealpha')) {
    fwrite(STDERR, "PHP exif + GD are required.\n");
    exit(1);
}

$type = @exif_imagetype($src);
if ($type === IMAGETYPE_PNG) {
    if (!function_exists('imagecreatefrompng')) {
        fwrite(STDERR, "GD PNG support is required (imagecreatefrompng).\n");
        exit(1);
    }
    $im = @imagecreatefrompng($src);
} elseif ($type === IMAGETYPE_JPEG) {
    if (!function_exists('imagecreatefromjpeg')) {
        fwrite(STDERR, "GD JPEG support is required (imagecreatefromjpeg).\n");
        exit(1);
    }
    $im = @imagecreatefromjpeg($src);
} else {
    fwrite(STDERR, "Unsupported source image type. Expected PNG or JPEG.\n");
    exit(1);
}

if (!$im) {
    fwrite(STDERR, "Failed to read source image: {$src}\n");
    exit(1);
}

imagealphablending($im, true);
imagesavealpha($im, true);

$sizes = [
    16  => 'favicon-16x16.png',
    32  => 'favicon-32x32.png',
    48  => 'favicon-48x48.png',
    180 => 'apple-touch-icon.png',
];

foreach ($sizes as $size => $name) {
    $dst = imagecreatetruecolor($size, $size);
    imagesavealpha($dst, true);
    imagealphablending($dst, false);
    $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
    imagefill($dst, 0, 0, $transparent);

    $srcW = imagesx($im);
    $srcH = imagesy($im);

    if (!imagecopyresampled($dst, $im, 0, 0, 0, 0, $size, $size, $srcW, $srcH)) {
        fwrite(STDERR, "Resize failed for size {$size}\n");
        exit(1);
    }

    $out = $root . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . $name;
    if (!imagepng($dst, $out, 9)) {
        fwrite(STDERR, "Failed to write: {$out}\n");
        exit(1);
    }
    imagedestroy($dst);
}

imagedestroy($im);
fwrite(STDOUT, "Generated favicon PNGs successfully.\n");

