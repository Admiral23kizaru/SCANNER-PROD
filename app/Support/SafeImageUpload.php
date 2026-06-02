<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;

class SafeImageUpload
{
    public static function storePublic(
        UploadedFile $file,
        string $dir,
        ?string $previousRelativePath = null,
        ?string $filenamePrefix = null
    ): string {
        $base = public_path('storage' . DIRECTORY_SEPARATOR . $dir);
        if (!File::exists($base)) {
            File::makeDirectory($base, 0755, true);
        }

        self::deletePrevious($base, $dir, $previousRelativePath);

        $info = @getimagesize($file->getRealPath());
        if (!$info || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
            throw new RuntimeException('Unsupported image upload.');
        }

        $ext = match ($info[2]) {
            IMAGETYPE_PNG => 'png',
            IMAGETYPE_WEBP => 'webp',
            default => 'jpg',
        };

        $safePrefix = $filenamePrefix ? Str::slug($filenamePrefix, '-') . '-' : '';
        $filename = $safePrefix . Str::uuid()->toString() . '.' . $ext;
        $target = $base . DIRECTORY_SEPARATOR . $filename;

        if (!extension_loaded('gd')) {
            $file->move($base, $filename);
            return $dir . '/' . $filename;
        }

        $image = self::createImage($file->getRealPath(), $info[2]);
        if (!$image) {
            throw new RuntimeException('Invalid image upload.');
        }

        try {
            self::writeImage($image, $target, $info[2]);
        } finally {
            imagedestroy($image);
        }

        return $dir . '/' . $filename;
    }

    private static function createImage(string $path, int $type): mixed
    {
        return match ($type) {
            IMAGETYPE_PNG => imagecreatefrompng($path),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? imagecreatefromwebp($path) : false,
            default => imagecreatefromjpeg($path),
        };
    }

    private static function writeImage(mixed $image, string $target, int $type): void
    {
        $ok = match ($type) {
            IMAGETYPE_PNG => imagepng($image, $target, 6),
            IMAGETYPE_WEBP => function_exists('imagewebp') ? imagewebp($image, $target, 85) : false,
            default => imagejpeg($image, $target, 85),
        };

        if (!$ok) {
            throw new RuntimeException('Failed to save image upload.');
        }
    }

    private static function deletePrevious(string $base, string $dir, ?string $previousRelativePath): void
    {
        if (!$previousRelativePath) {
            return;
        }

        $clean = ltrim(preg_replace('#^(public/|storage/|/storage/)#', '', $previousRelativePath) ?? $previousRelativePath, '/');
        if (!str_starts_with($clean, trim($dir, '/') . '/')) {
            return;
        }

        $previous = realpath(public_path('storage' . DIRECTORY_SEPARATOR . $clean));
        $baseReal = realpath($base);
        if ($previous && $baseReal && str_starts_with($previous, $baseReal) && File::exists($previous)) {
            File::delete($previous);
        }
    }
}
