<?php

declare(strict_types=1);

namespace Givanov95\LaravelAttachments\Tests;

use Givanov95\LaravelAttachments\AttachmentsServiceProvider;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Storage;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [AttachmentsServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        /** @var Application $app */
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        $app['config']->set('attachments.disk', 'fake');
        $app['config']->set('filesystems.disks.fake', [
            'driver' => 'local',
            'root'   => sys_get_temp_dir().'/la-test-'.uniqid(),
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('fake');
    }
}
