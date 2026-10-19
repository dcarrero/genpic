<?php

namespace Tests\Unit\Services;

use App\Services\StorageService;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StorageServiceTest extends TestCase
{
    public function test_disk_returns_local_in_testing(): void
    {
        $service = new StorageService();
        $this->assertEquals('local', $service->disk());
    }

    public function test_store_creates_file_with_correct_path_pattern(): void
    {
        Storage::fake('local');

        $service = new StorageService();
        $path = $service->store('image content', 'originals', 'test-uuid', 'jpg');

        $this->assertStringStartsWith('originals/test-uuid/', $path);
        $this->assertStringEndsWith('.jpg', $path);
        Storage::disk('local')->assertExists($path);
    }

    public function test_exists_checks_file_on_correct_disk(): void
    {
        Storage::fake('local');
        $service = new StorageService();

        $path = $service->store('content', 'generations', 'uuid-1', 'png');

        $this->assertTrue($service->exists($path));
        $this->assertFalse($service->exists('nonexistent/path.png'));
    }

    public function test_delete_removes_file(): void
    {
        Storage::fake('local');
        $service = new StorageService();

        $path = $service->store('content', 'watermarked', 'uuid-2', 'jpg');
        $this->assertTrue($service->exists($path));

        $service->delete($path);
        $this->assertFalse($service->exists($path));
    }

    public function test_size_returns_file_size_in_bytes(): void
    {
        Storage::fake('local');
        $service = new StorageService();

        $content = str_repeat('x', 1024);
        $path = $service->store($content, 'exports', 'uuid-3', 'jpg');

        $this->assertEquals(1024, $service->size($path));
    }
}
