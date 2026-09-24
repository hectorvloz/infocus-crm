<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class DocumentThumbnail
{
    public function path(string $documentId, string $sourcePath): ?string
    {
        $disk = Storage::disk('public');
        if (!$disk->exists($sourcePath) || !function_exists('imagewebp') || !function_exists('imagecreatefromstring')) {
            return null;
        }

        $targetPath = 'document-thumbnails/' . hash('sha256', $documentId . '|' . $sourcePath) . '.webp';
        if ($disk->exists($targetPath)) {
            return $disk->path($targetPath);
        }

        $source = $disk->path($sourcePath);
        $size = @getimagesize($source);
        if (!$size || $size[0] < 1 || $size[1] < 1 || $size[0] * $size[1] > 40000000) {
            return null;
        }

        $image = @imagecreatefromstring((string) file_get_contents($source));
        if (!$image) {
            return null;
        }

        $width = (int) $size[0];
        $height = (int) $size[1];
        $targetWidth = min(640, $width);
        $targetHeight = max(1, (int) round($targetWidth * 10 / 16));
        $target = imagecreatetruecolor($targetWidth, $targetHeight);
        imagealphablending($target, false);
        imagesavealpha($target, true);
        $transparent = imagecolorallocatealpha($target, 0, 0, 0, 127);
        imagefilledrectangle($target, 0, 0, $targetWidth, $targetHeight, $transparent);
        $sourceRatio = $width / $height;
        $targetRatio = 16 / 10;
        if ($sourceRatio > $targetRatio) {
            $cropHeight = $height;
            $cropWidth = (int) round($height * $targetRatio);
            $cropX = (int) floor(($width - $cropWidth) / 2);
            $cropY = 0;
        } else {
            $cropWidth = $width;
            $cropHeight = (int) round($width / $targetRatio);
            $cropX = 0;
            $cropY = (int) floor(($height - $cropHeight) / 2);
        }
        imagecopyresampled($target, $image, 0, 0, $cropX, $cropY, $targetWidth, $targetHeight, $cropWidth, $cropHeight);
        imagedestroy($image);

        $disk->makeDirectory('document-thumbnails');
        $tempPath = $disk->path($targetPath . '.' . uniqid('', true) . '.tmp');
        $written = @imagewebp($target, $tempPath, 76);
        imagedestroy($target);
        if (!$written) {
            @unlink($tempPath);
            return null;
        }
        if (!@rename($tempPath, $disk->path($targetPath))) {
            @unlink($tempPath);
        }

        return $disk->exists($targetPath) ? $disk->path($targetPath) : null;
    }

    public function delete(string $documentId, string $sourcePath): void
    {
        Storage::disk('public')->delete('document-thumbnails/' . hash('sha256', $documentId . '|' . $sourcePath) . '.webp');
    }
}
