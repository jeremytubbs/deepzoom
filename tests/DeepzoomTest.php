<?php

use Jeremytubbs\Deepzoom\DeepzoomFactory;
use League\Flysystem\Config;
use League\Flysystem\Local\LocalFilesystemAdapter;

beforeEach(
    closure: function () {
        $this->testDataDir = __DIR__ . '/data/';
        $this->directory = '/deepzoom';
        $this->adapter = new LocalFilesystemAdapter(sys_get_temp_dir());
        $this->adapter->createDirectory($this->directory, new Config());
    }
);

afterEach(
    closure: function () {
        $this->adapter->deleteDirectory($this->directory);
    }
);

it('makes tiles', function () {
    $deepzoom = DeepzoomFactory::create([
        'path' => sys_get_temp_dir() . $this->directory,
        'driver' => 'gd',
        'format' => 'jpeg',
    ]);

    $response = $deepzoom->makeTiles($this->testDataDir . 'image.png');

    $this->assertEquals('ok', $response['status']);
});
