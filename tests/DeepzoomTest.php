<?php

namespace Jeremytubbs\Deepzoom\Tests;

use Jeremytubbs\Deepzoom\DeepzoomFactory;
use PHPUnit\Framework\TestCase;

class DeepzoomTest extends TestCase
{
    public const TEST_DIR = __DIR__ . '/test-data/';

    private $tempDir;

    public function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/jt-dz-' . (string)mt_rand();
        mkdir($this->tempDir);
    }

    public function testGeneratesImage(): void
    {
        $dz = DeepzoomFactory::create([
            'path' => $this->tempDir,
            'driver' => 'imagick',
            'format' => 'jpeg',
        ]);

        $response = $dz->makeTiles(self::TEST_DIR . 'ducks.jpg');

        $this->assertEquals('ok', $response['status']);
    }
}
