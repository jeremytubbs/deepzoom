<?php

namespace Jeremytubbs\Deepzoom;

use Intervention\Image\ImageManagerStatic as Image;
use League\Flysystem\Filesystem;
use League\Flysystem\Adapter\Local;

class Deepzoom
{

    private $tileSize;
    private $tileOverlap;
    private $tileFormat;

    public function __construct()
    {
        Image::configure(array('driver' => 'GD'));
        $this->tileSize = 256;
        $this->tileOverlap = 1;
        $this->tileFormat = 'jpg';

        $this->adapter = new Local(__DIR__.'/../../../public/images');
        $this->filesystem = new Filesystem($this->adapter);
    }

    public function test()
    {
        // path to a test image
        $image = public_path('images/TBA-Studio.jpg');
        $img = Image::make($image);

        // get image width and height
        $height = $img->height();
        $width = $img->width();
        $maxDimension = max([$width, $height]);

        // calculate the number of levels
        $numLevels = $this->getNumLevels($maxDimension);

        // folder name = level
        $filename = pathinfo($image)['basename'];
        $folder = pathinfo($image)['filename'].'_files';
        $this->filesystem->createDir($folder);

        foreach(range(0,$numLevels - 1) as $level) {
            $level_folder = $folder.'/'.$level;
            $this->filesystem->createDir($level_folder);
            // calculate scale for level
            $scale = $this->getScaleForLevel($numLevels, $level);
            // calculate dimensions for levels
            $dimension = $this->getDimensionForLevel($width, $height, $scale);
            $img = Image::make($image)->resize($dimension['width'], $dimension['height']);
            $img->save(__DIR__."/../../../public/images/$level_folder/$filename");
            //$this->createLevelTiles($dimension['width'], $dimension['height'], $level, $level_folder);
        }

        return $folder;
    }

    public function getNumLevels($maxDimension)
    {
        return (int)ceil(log($maxDimension,2)) + 1;
    }

    public function getNumTiles($width, $height)
    {
        $columns = (int)ceil(floatval($width) / $this->tileSize);
        $rows = (int)ceil(floatval($height) / $this->tileSize);
        return ['columns' => $columns, 'rows' => $rows];
    }

    public function getScaleForLevel($numLevels, $level)
    {
        $maxLevel = $numLevels - 1;
        return pow(0.5,$maxLevel - $level);
    }

    public function getDimensionForLevel($width, $height, $scale)
    {
        $width = (int)ceil($width * $scale);
        $height = (int)ceil($height * $scale);
        return ['width' => $width, 'height' => $height];
    }

    public function createLevelTiles($width, $height, $level, $folder)
    {
        // get column and row count for level
        $tiles = $this->getNumTiles($width, $height);
        foreach (range(0, $tiles['columns'] - 1) as $column) {
            foreach (range(0, $tiles['rows'] - 1) as $row) {
                $tile_file = "$folder/$column_$row.$this->tileFormat";
            }
        }
    }

    // public function getTileBoundsPosition($column, $row) {
    //     $offsetX = $column == 0 ? 0 : $this->_tileOverlap;
    //     $offsetY = $row == 0 ? 0 : $this->_tileOverlap;
    //     $x = ($column * $this->_tileSize) - $offsetX;
    //     $y = ($row * $this->_tileSize) - $offsetY;

    //     return ['x' => $x, 'y' => $y];
    // }

    // public function getTileBounds($level, $column, $row) {
    //     $position = $this->getTileBoundsPosition($column, $row);

    //     $dimension = $this->getDimension($level);
    //     $width = $this->_tileSize + ($column == 0 ? 1 : 2) * $this->_tileOverlap;
    //     $height = $this->_tileSize + ($row == 0 ? 1 : 2) * $this->_tileOverlap;
    //     $newWidth = min($width, $dimension['width'] - $position['x']);
    //     $newHeight = min($height, $dimension['height'] - $position['y']);

    //     return array_merge($position,array( 'width' => $newWidth,'height' => $newHeight));
    // }

}
