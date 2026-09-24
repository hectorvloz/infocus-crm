<?php

namespace Tests\Unit;

use App\Http\Controllers\DocumentosController;
use App\Support\DocumentThumbnail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentThumbnailTest extends TestCase
{
    public function test_project_image_has_small_cached_board_thumbnail_and_original_preview(): void
    {
        if (!function_exists('imagewebp')) {
            $this->markTestSkipped('GD WebP no disponible.');
        }
        Storage::fake('local');
        Storage::fake('public');
        Storage::disk('public')->makeDirectory('documentos/test');
        $sourcePath = 'documentos/test/cover.jpg';
        $source = imagecreatetruecolor(1600, 1000);
        for ($y = 0; $y < 1000; $y += 20) {
            for ($x = 0; $x < 1600; $x += 20) {
                $color = imagecolorallocate($source, ($x * 7 + $y) % 255, ($x + $y * 3) % 255, ($x * 5 + $y * 11) % 255);
                imagefilledrectangle($source, $x, $y, $x + 19, $y + 19, $color);
            }
        }
        imagejpeg($source, Storage::disk('public')->path($sourcePath), 90);
        imagedestroy($source);
        Storage::put('documentos.json', json_encode([[
            'id' => 'd1', 'storage' => 'local', 'path' => $sourcePath,
            'mime' => 'image/jpeg', 'original_name' => 'cover.jpg',
        ]]));

        $service = new DocumentThumbnail();
        $thumbnail = $service->path('d1', $sourcePath);
        $this->assertNotNull($thumbnail);
        $this->assertSame([640, 400], array_slice(getimagesize($thumbnail), 0, 2));
        $this->assertLessThan(filesize(Storage::disk('public')->path($sourcePath)) / 3, filesize($thumbnail));
        $this->assertSame($thumbnail, $service->path('d1', $sourcePath));

        $controller = new DocumentosController();
        $boardResponse = $controller->thumbnail('d1');
        $originalResponse = $controller->preview('d1');
        $this->assertSame('image/webp', $boardResponse->headers->get('Content-Type'));
        $this->assertStringContainsString('max-age=', $boardResponse->headers->get('Cache-Control'));
        $this->assertSame('image/jpeg', $originalResponse->headers->get('Content-Type'));
        $this->assertSame(Storage::disk('public')->path($sourcePath), $originalResponse->getFile()->getPathname());
    }
}
