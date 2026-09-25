<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;

class ImageStorage
{
    private const MAX_UPLOAD_EDGE = 2048;

    private const THUMB_EDGE = 400;

    /**
     * Yüklenen ürün fotoğrafını EXIF yönü düzeltilmiş, uzun kenarı en fazla 2048 px JPEG olarak kaydeder.
     *
     * @return array{path: string, width: int, height: int}
     */
    public function storeUpload(UploadedFile $file, string $directory, string $name = 'original'): array
    {
        $image = Image::read($file->getRealPath())->orient()->scaleDown(self::MAX_UPLOAD_EDGE, self::MAX_UPLOAD_EDGE);
        $path = $directory.'/'.$name.'-'.now()->timestamp.'.jpg';

        Storage::disk('public')->put($path, (string) $image->toJpeg(90));

        return ['path' => $path, 'width' => $image->width(), 'height' => $image->height()];
    }

    /**
     * AI'dan gelen görseli kaydeder ve bir küçük resim üretir.
     *
     * @return array{path: string, thumb_path: string, width: int, height: int}
     */
    public function storeResult(string $binary, string $directory, string $name): array
    {
        $image = Image::read($binary);
        $path = $directory.'/'.$name.'.png';
        $thumbPath = $directory.'/thumbs/'.$name.'.jpg';

        Storage::disk('public')->put($path, (string) $image->toPng());
        Storage::disk('public')->put($thumbPath, (string) (clone $image)->scaleDown(self::THUMB_EDGE, self::THUMB_EDGE)->toJpeg(82));

        return ['path' => $path, 'thumb_path' => $thumbPath, 'width' => $image->width(), 'height' => $image->height()];
    }

    public function storeBinary(string $binary, string $path): string
    {
        Storage::disk('public')->put($path, (string) Image::read($binary)->toPng());

        return $path;
    }

    /** Admin panelden yüklenen katalog görselleri. */
    public function storeCatalog(UploadedFile $file, string $folder): string
    {
        $image = Image::read($file->getRealPath())->orient()->scaleDown(1200, 1200);
        $path = 'catalog/'.$folder.'/'.uniqid().'.jpg';
        Storage::disk('public')->put($path, (string) $image->toJpeg(88));

        return $path;
    }

    public function read(string $path): string
    {
        return Storage::disk('public')->get($path);
    }

    public function mime(string $path): string
    {
        return Storage::disk('public')->mimeType($path) ?: 'image/jpeg';
    }
}
