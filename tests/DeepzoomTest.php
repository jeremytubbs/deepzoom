<?php

namespace Jeremytubbs\Deepzoom\Tests;

use Jeremytubbs\Deepzoom\DeepzoomFactory;
use League\Flysystem\Config;
use League\Flysystem\Local\LocalFilesystemAdapter;
use PHPUnit\Framework\TestCase;

class DeepzoomTest extends TestCase
{
    public const TEST_DIR = __DIR__ . '/test-data/';

    private $adapter;

    private $workingDirectory;

    public function setUp(): void
    {
        $this->workingDirectory = '/jt-dz' . (string)mt_rand();

        $this->adapter = new LocalFilesystemAdapter(sys_get_temp_dir());
        $this->adapter->createDirectory($this->workingDirectory, new Config());
    }

    public function tearDown(): void
    {
        $this->adapter->deleteDirectory($this->workingDirectory);
    }

    public function testGeneratesImage(): void
    {
        $dz = DeepzoomFactory::create([
            'path' => sys_get_temp_dir() . $this->workingDirectory,
            'driver' => 'imagick',
            'format' => 'jpeg',
        ]);

        $response = $dz->makeTiles(self::TEST_DIR . 'ducks.jpg');

        $this->assertEquals('ok', $response['status']);
    }
}
