<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProductImageService
{
    public function store(Product $product, UploadedFile $file, ?string $alt): ProductImage
    {
        $path = $this->storeWebp($file, "products/{$product->id}");
        try {
            return DB::transaction(fn () => $product->images()->create([
                'path' => $path,
                'alt_text' => $alt,
                'sort_order' => $product->images()->lockForUpdate()->count(),
                'is_primary' => ! $product->images()->exists(),
            ]));
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete($path);
            throw $exception;
        }
    }

    public function storeWebp(UploadedFile $file, string $directory): string
    {
        $contents = file_get_contents($file->getRealPath());
        $source = $contents === false ? false : imagecreatefromstring($contents);
        if ($source === false) {
            throw ValidationException::withMessages(['image' => 'تعذر معالجة الصورة المرفوعة.']);
        }
        $width = imagesx($source);
        $height = imagesy($source);
        if ($width > 1600) {
            $resized = imagescale($source, 1600, (int) round($height * (1600 / $width)), IMG_BICUBIC_FIXED);
            imagedestroy($source);
            $source = $resized;
        }
        imagepalettetotruecolor($source);
        imagealphablending($source, true);
        imagesavealpha($source, true);
        ob_start();
        imagewebp($source, null, 86);
        $webp = ob_get_clean();
        imagedestroy($source);
        if (! is_string($webp) || $webp === '') {
            throw ValidationException::withMessages(['image' => 'تعذر تحسين الصورة المرفوعة.']);
        }
        $path = trim($directory, '/').'/'.Str::uuid().'.webp';
        Storage::disk('public')->put($path, $webp);

        return $path;
    }
}
