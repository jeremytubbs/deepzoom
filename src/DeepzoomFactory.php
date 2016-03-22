<?php

namespace Jeremytubbs\Deepzoom;

use InvalidArgumentException;
use Intervention\Image\ImageManager;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;

/**
 * Class DeepzoomFactory
 * @package Jeremytubbs\Deepzoom
 */
class DeepzoomFactory
{
    /**
     * @var array
     */
    protected $config;

    /**
     * @param array $config
     */
	public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    /**
     * @return Deepzoom
     */
    public function getDeepzoom()
    {
    	$deepzoom = new Deepzoom(
            $this->getFilesystem(),
            $this->getImageManager(),
            $this->getTileFormat(),
            $this->getPathPrefix()
        );

        return $deepzoom;
    }

    /**
     * @return Filesystem|void
     */
    public function getFilesystem()
    {
        if (!isset($this->config['path'])) {
            throw new InvalidArgumentException('A "source" file system must be set.');
        }

        if (is_string($this->config['path'])) {
            return new Filesystem(
                new Local($this->config['path'])
            );
        }

        return $this->config['source'];
    }

    /**
     * @return ImageManager
     */
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

    public function getTileFormat()
    {
        $tileFormat = 'jpg';

        if (isset($this->config['format'])) {
            $tileFormat = $this->config['format'];
        }

        return $tileFormat;
    }

    public function getPathPrefix()
    {
        if (isset($this->config['path'])) {
            $pathPrefix = $this->config['path'];
        }

        return $pathPrefix;
    }

    /**
     * @param array $config
     * @return Deepzoom
     */
    public static function create(array $config = [])
    {
        return (new self($config))->getDeepzoom();
    }
}