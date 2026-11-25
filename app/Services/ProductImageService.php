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
    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'avif'];

    public function store(Product $product, UploadedFile $file, ?string $alt): ProductImage
    {
        $path = $this->storeOriginal($file, "products/{$product->id}");
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

    public function storeOriginal(UploadedFile $file, string $directory): string
    {
        $extension = strtolower($file->getClientOriginalExtension());

        if (! in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            throw ValidationException::withMessages([
                'image' => 'صيغة الصورة غير مدعومة. استخدمي JPG أو PNG أو WEBP أو GIF أو AVIF.',
            ]);
        }

        $filename = Str::uuid().'.'.$extension;
        $path = trim($directory, '/').'/'.$filename;

        if (Storage::disk('public')->putFileAs(trim($directory, '/'), $file, $filename) === false) {
            throw ValidationException::withMessages([
                'image' => 'تعذر حفظ الصورة المرفوعة. حاولي مرة أخرى.',
            ]);
        }

        return $path;
    }

    public function move(ProductImage $image, string $direction): void
    {
        DB::transaction(function () use ($image, $direction): void {
            $images = ProductImage::query()->where('product_id', $image->product_id)->orderBy('sort_order')->orderBy('id')->lockForUpdate()->get()->values();
            $index = $images->search(fn (ProductImage $candidate) => $candidate->is($image));
            $target = $direction === 'up' ? $index - 1 : $index + 1;
            if (! is_int($index) || ! isset($images[$target])) {
                return;
            }
            [$images[$index], $images[$target]] = [$images[$target], $images[$index]];
            $images->each(fn (ProductImage $candidate, int $position) => $candidate->update(['sort_order' => $position]));
        });
    }
}
