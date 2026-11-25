<?php

namespace Tests\Unit;

use Tests\TestCase;

class PublicMediaDiskTest extends TestCase
{
    public function test_public_media_defaults_to_the_web_root_storage_directory(): void
    {
        $this->assertSame(public_path('storage'), config('filesystems.disks.public.root'));
    }
}
