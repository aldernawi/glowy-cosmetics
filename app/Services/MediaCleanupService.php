<?php

namespace App\Services;

use App\Models\ProductImage;
use Illuminate\Support\Facades\Storage;

class MediaCleanupService
{
    public function deleteProductImageIfUnreferenced(string $path): void
    {
        if ($path === '' || ProductImage::query()->where('path', $path)->exists()) {
            return;
        }

        Storage::disk('public')->delete($path);
    }
}
