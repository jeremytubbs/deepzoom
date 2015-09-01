<?php

namespace Jeremytubbs\Deepzoom;

use Intervention\Image\ImageManager;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;

class DeepzoomFactory
{
	protected $config;

	public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    public function getDeepzoom()
    {
    	$deepzoom = new Deepzoom(
            $this->getPath(),
            $this->getImageManager()
        );

        return $deepzoom;
    }

    public function getPath()
    {
        if (!isset($this->config['path'])) {
            return;
        }

        if (is_string($this->config['path'])) {
            return new Filesystem(
                new Local($this->config['path'])
            );
        }

        return $this->config['Path'];
    }

    public function getImageManager()
    {
        $driver = 'gd';

        if (isset($this->config['driver'])) {
            $driver = $this->config['driver'];
        }

        return new ImageManager([
            'driver' => $driver,
        ]);
    }

    public static function create(array $config = [])
    {
        return (new self($config))->getDeepzoom();
    }
}