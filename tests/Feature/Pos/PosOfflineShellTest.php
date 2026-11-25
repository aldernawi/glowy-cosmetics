<?php

namespace Tests\Feature\Pos;

use Tests\TestCase;

class PosOfflineShellTest extends TestCase
{
    private const FILES = [
        'resources/views/pos/app.blade.php', 'public/pos-assets/app.css', 'public/pos-assets/app.js',
        'public/pos-assets/core.js', 'public/pos-assets/db.js', 'public/pos-assets/manifest.webmanifest',
    ];

    public function test_the_shell_is_public_stateless_and_holds_no_session(): void
    {
        $this->get(route('pos.app'))->assertOk()
            ->assertSee('كاشير Glowy')
            ->assertHeader('X-Robots-Tag', 'noindex')
            ->assertCookieMissing(config('session.cookie'));
    }

    public function test_the_service_worker_version_changes_with_the_shell_files(): void
    {
        $expected = substr(hash('sha256', implode('', array_map(fn (string $file) => file_get_contents(base_path($file)), self::FILES))), 0, 12);
        $worker = file_get_contents(public_path('pos-sw.js'));

        $this->assertStringContainsString("const VERSION = '{$expected}';", $worker, "Set VERSION in public/pos-sw.js to '{$expected}' so registers download the new files.");
        foreach (array_slice(self::FILES, 1) as $file) {
            $this->assertStringContainsString("'".substr($file, strlen('public'))."'", $worker);
        }
    }
}
