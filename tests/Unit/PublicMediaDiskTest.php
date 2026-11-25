<?php

namespace Tests\Unit;

use Tests\TestCase;

class PublicMediaDiskTest extends TestCase
{
    public function test_public_media_defaults_to_the_web_root_storage_directory(): void
    {
        $this->assertSame(public_path('storage'), config('filesystems.disks.public.root'));
    }

    public function test_an_empty_environment_value_falls_back_to_the_web_root_storage_directory(): void
    {
        $original = $_SERVER['FILESYSTEM_PUBLIC_ROOT'] ?? null;
        $_SERVER['FILESYSTEM_PUBLIC_ROOT'] = '';

        try {
            $config = require config_path('filesystems.php');
        } finally {
            if ($original === null) {
                unset($_SERVER['FILESYSTEM_PUBLIC_ROOT']);
            } else {
                $_SERVER['FILESYSTEM_PUBLIC_ROOT'] = $original;
            }
        }

        $this->assertSame(public_path('storage'), $config['disks']['public']['root']);
    }
}
