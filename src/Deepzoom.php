<?php

namespace Jeremytubbs\Deepzoom;

use Intervention\Image\ImageManager;
use League\Flysystem\FilesystemInterface;

/**
 * Class Deepzoom
 * @package Jeremytubbs\Deepzoom
 */
class Deepzoom
{
    protected $path;
    protected $imageManager;
    protected $tileFormat;

    private $tileSize;
    private $tileOverlap;
    private $pathPrefix;


    /**
     * @param FilesystemInterface $path
     * @param ImageManager $imageManager
     */
    public function __construct(FilesystemInterface $path, ImageManager $imageManager, $tileFormat, $pathPrefix)
    {
        $this->setImageManager($imageManager);
        $this->setPath($path);
        $this->tileSize = 256;
        $this->tileOverlap = 1;
        $this->tileFormat = $tileFormat;
        $this->pathPrefix = $pathPrefix;
    }

    /**
     * @param $image
     * @param null $file
     * @param null $folder
     * @return array|string
     */
    public function makeTiles($image, $file = NULL, $folder = NULL)
    {
        // path to a test image
        $img = $this->imageManager->make($image);

        // get image width and height
        $height = $img->height();
        $width = $img->width();

        $maxDimension = max([$width, $height]);

        // calculate the number of levels
        $numLevels = $this->getNumLevels($maxDimension);

        // set filename or use path filename
        $filename = $file !== NULL ? $file : pathinfo($image)['filename'];
        $filename = $this->cleanupFilename($filename);

        // set folder or use path filename
        $foldername = $folder !== NULL ? $folder : pathinfo($image)['filename'];
        $foldername = $this->cleanupFolderName($foldername);

        // check for spaces in names
        $check = $this->checkJsonFilename($filename);
        if ($check != 'ok') return $check;

        $folder = $foldername.'/'.$filename.'_files';
        $this->path->createDir($folder);

        foreach(range($numLevels - 1, 0) as $level) {
            $level_folder = $folder.'/'.$level;
            $this->path->createDir($level_folder);
            // calculate scale for level
            $scale = $this->getScaleForLevel($numLevels, $level);
            // calculate dimensions for levels
            $dimension = $this->getDimensionForLevel($width, $height, $scale);
            // create tiles for level
            $this->createLevelTiles($dimension['width'], $dimension['height'], $level, $level_folder, $img);
        }

        $DZI = $this->createDZI($height, $width);
        $this->path->put($foldername.'/'.$filename.'.dzi', $DZI);

        $JSONP = $this->createJSONP($filename, $height, $width);
        $this->path->put($foldername.'/'.$filename.'.js', $JSONP);

        $data = [
            'output' => [
                'JSONP'  => "$this->pathPrefix/$foldername/$filename.js",
                'DZI'    => "$this->pathPrefix/$foldername/$filename.dzi",
                '_files' => "$this->pathPrefix/$foldername/".$filename."_files",
            ],
            'source' => $image,
        ];

        // used with Laravel to fire event
        if ( defined('LARAVEL_START') ) \Event::fire('deepzoom', [$data]);

        return [
            'status' => 'ok',
            'data' => $data,
            'message' => 'Everything is okay!'
        ];
    }

    /**
     * @param $maxDimension
     * @return int
     */
    public function getNumLevels($maxDimension)
    {
        return (int)ceil(log($maxDimension,2)) + 1;
    }

    /**
     * @param $width
     * @param $height
     * @return array
     */
    public function getNumTiles($width, $height)
    {
        $columns = (int)ceil(floatval($width) / $this->tileSize);
        $rows = (int)ceil(floatval($height) / $this->tileSize);
        return ['columns' => $columns, 'rows' => $rows];
    }

    /**
     * @param $numLevels
     * @param $level
     * @return number
     */
    public function getScaleForLevel($numLevels, $level)
    {
        $maxLevel = $numLevels - 1;
        return pow(0.5,$maxLevel - $level);
    }

    /**
     * @param $width
     * @param $height
     * @param $scale
     * @return array
     */
    public function getDimensionForLevel($width, $height, $scale)
    {
        $width = (int)ceil($width * $scale);
        $height = (int)ceil($height * $scale);
        return ['width' => $width, 'height' => $height];
    }

    /**
     * @param $width
     * @param $height
     * @param $level
     * @param $folder
     * @param $img
     */
    public function createLevelTiles($width, $height, $level, $folder, $img)
    {
        // create new image at scaled dimensions
        $img = $img->resize($width, $height);
        // get column and row count for level
        $tiles = $this->getNumTiles($width, $height);

        foreach (range(0, $tiles['columns'] - 1) as $column) {
            foreach (range(0, $tiles['rows'] - 1) as $row) {
                $tileImg = clone $img;
                $tile_file = $column.'_'.$row.'.'.$this->tileFormat;
                $bounds = $this->getTileBounds($level,$column,$row,$width,$height);
                $tileImg->crop($bounds['width'],$bounds['height'],$bounds['x'],$bounds['y']);
                $tileImg->encode($this->tileFormat);
                $this->path->put("$folder/$tile_file", $tileImg);
                unset($tileImg);
            }
        }
    }

    /**
     * @param $column
     * @param $row
     * @return array
     */
    public function getTileBoundsPosition($column, $row)
    {
        $offsetX = $column == 0 ? 0 : $this->tileOverlap;
        $offsetY = $row == 0 ? 0 : $this->tileOverlap;
        $x = ($column * $this->tileSize) - $offsetX;
        $y = ($row * $this->tileSize) - $offsetY;

        return ['x' => $x, 'y' => $y];
    }

    /**
     * @param $level
     * @param $column
     * @param $row
     * @param $w
     * @param $h
     * @return array
     */
    public function getTileBounds($level, $column, $row, $w, $h)
    {
        $position = $this->getTileBoundsPosition($column, $row);

        $width = $this->tileSize + ($column == 0 ? 1 : 2) * $this->tileOverlap;
        $height = $this->tileSize + ($row == 0 ? 1 : 2) * $this->tileOverlap;
        $newWidth = min($width, $w - $position['x']);
        $newHeight = min($height, $h - $position['y']);

        return array_merge($position,['width' => $newWidth,'height' => $newHeight]);
    }

    /**
     * @param $height
     * @param $width
     * @return string
     */
    public function createDZI($height, $width)
    {
        return <<<EOF
<?xml version="1.0" encoding="UTF-8"?>
<Image xmlns="http://schemas.microsoft.com/deepzoom/2008"
       Format="$this->tileFormat"
       Overlap="$this->tileOverlap"
       TileSize="$this->tileSize" >
    <Size Height="$height"
          Width="$width" />
</Image>
EOF;
    }

    /**
     * @param $filename
     * @param $height
     * @param $width
     * @return string
     */
    public function createJSONP($filename, $height, $width)
    {
        return <<<EOF
$filename({
    Image: {
        xmlns: 'http://schemas.microsoft.com/deepzoom/2008',
        Format: '$this->tileFormat',
        Overlap: $this->tileOverlap,
        TileSize: $this->tileSize,
        Size: {
            Width: $width,
            Height: $height
        }
    }
});
EOF;
    }

    /**
     * @param $string
     * @return string
     */
    public function cleanupFilename($string)
    {
        // trim space
        $string = trim($string);
        // replace strings and dashes with underscore
        return str_replace(['/\s/', '-', ' '], '_', $string);
    }

    /**
     * @param $string
     * @return string
     */
    public function cleanupFolderName($string)
    {
        // trim space
        $string = trim($string);
        // replace strings and dashes with dash
        return str_replace(['/\s/', ' '], '-', $string);
    }

    /**
     * @param $string
     * @return array|string
     */
    public function checkJsonFilename($string) {
        // for JSONP filename cannot contain special characters
        $specialCharRegex = '/[\'^£%&*()}{@#~?><> ,|=+¬-]/';
        if (preg_match($specialCharRegex, $string)) {
            return [
                'status' => 'error',
                'message' => 'JSONP filename name must not contain special characters.'
            ];
        }
        // for JSONP filename cannot start with a number
        $stringFirstChar = substr($string, 0, 1);
        // if numeric add 'a' to begining of filename
        if (is_numeric($stringFirstChar)) {
            return [
                'status' => 'error',
                'message' => 'JSONP filenames must not start with a numeric value.'
            ];
        }
        return 'ok';
    }

    /**
     * @param ImageManager $imageManager
     */
    public function setImageManager(ImageManager $imageManager)
    {
        $this->imageManager = $imageManager;
    }

    /**
     * @return mixed
     */
    public function getImageManager()
    {
        return $this->imageManager;
    }

    /**
     * @param FilesystemInterface $path
     */
    public function setPath(FilesystemInterface $path)
    {
        $this->path = $path;
    }

    /**
     * @return mixed
     */
    public function getPath()
    {
        return $this->path;
    }
}
