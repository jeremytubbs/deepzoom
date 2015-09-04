<?php

namespace Jeremytubbs\Deepzoom;

use Intervention\Image\ImageManager;
use League\Flysystem\FilesystemInterface;

class Deepzoom
{
    protected $cache;
    protected $source;
    protected $imageManager;

    private $tileSize;
    private $tileOverlap;
    private $tileFormat;

    public function __construct(FilesystemInterface $path, ImageManager $imageManager)
    {
        $this->setImageManager($imageManager);
        $this->setPath($path);
        $this->tileSize = 256;
        $this->tileOverlap = 1;
        $this->tileFormat = 'jpg';
    }

    public function makeTiles($image, $file = NULL, $folder = NULL)
    {
        // path to a test image
        $img = $this->imageManager->make($image);

        // get image width and height
        $height = $img->height();
        $width = $img->width();
        unset($img);

        $maxDimension = max([$width, $height]);

        // calculate the number of levels
        $numLevels = $this->getNumLevels($maxDimension);

        // set filename or use path filename
        $filename = $file !== NULL ? $file : pathinfo($image)['filename'];

        // set folder or use path filename
        $foldername = $folder !== NULL ? $folder : pathinfo($image)['filename'];

        // check for spaces in names
        $check = $this->checkFileFolderNames($filename, $folder);
        if ($check != 'ok') return $check;

        $folder = $foldername.'/'.$filename.'_files';
        $this->path->createDir($folder);

        foreach(range(0,$numLevels - 1) as $level) {
            $level_folder = $folder.'/'.$level;
            $this->path->createDir($level_folder);
            // calculate scale for level
            $scale = $this->getScaleForLevel($numLevels, $level);
            // calculate dimensions for levels
            $dimension = $this->getDimensionForLevel($width, $height, $scale);
            // create tiles for level
            $this->createLevelTiles($dimension['width'], $dimension['height'], $level, $level_folder, $image);
        }

        $DZI = $this->createDZI($height, $width);
        $this->path->put($foldername.'/'.$filename.'.dzi', $DZI);

        $JSONP = $this->createJSONP($filename, $height, $width);
        $this->path->put($foldername.'/'.$filename.'.js', $JSONP);

        return [
            'status' => 'ok',
            'data' => [
                    'JSONP' => "$foldername/$filename.js",
                    'DZI' => "$foldername/$filename.dzi",
                    '_files' => "$foldername/$filename"."_files"
                ],
            'message' => 'Everything is okay!'
        ];
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

    public function createLevelTiles($width, $height, $level, $folder, $image)
    {
        // create new image at scaled dimensions
        $img = $this->imageManager->make($image)->resize($width, $height);
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
        unset($img);
    }

    public function getTileBoundsPosition($column, $row)
    {
        $offsetX = $column == 0 ? 0 : $this->tileOverlap;
        $offsetY = $row == 0 ? 0 : $this->tileOverlap;
        $x = ($column * $this->tileSize) - $offsetX;
        $y = ($row * $this->tileSize) - $offsetY;

        return ['x' => $x, 'y' => $y];
    }

    public function getTileBounds($level, $column, $row, $w, $h)
    {
        $position = $this->getTileBoundsPosition($column, $row);

        $width = $this->tileSize + ($column == 0 ? 1 : 2) * $this->tileOverlap;
        $height = $this->tileSize + ($row == 0 ? 1 : 2) * $this->tileOverlap;
        $newWidth = min($width, $w - $position['x']);
        $newHeight = min($height, $h - $position['y']);

        return array_merge($position,['width' => $newWidth,'height' => $newHeight]);
    }

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

    public function createJSONP($filename, $height, $width)
    {
        return <<<EOF
$filename({
    Image: {
        xmlns:    'http://schemas.microsoft.com/deepzoom/2008',
        Format:   '$this->tileFormat',
        Overlap:  $this->tileOverlap,
        TileSize: $$this->tileSize
        Size: {
            Width: 676,
            Height:  571
        }
    }
});
EOF;
    }

    public function checkFileFolderNames($file, $folder) {
        // check for space
        if (preg_match('/\s/',$file) || preg_match('/\s/',$folder)) {
            return [
                'status' => 'error',
                'message' => 'Filename and folder name must not contain spaces.'
            ];
        }
        // check for special characters
        $specialCharRegex = '/[\'^£$%&*()}{@#~?><>,|=_+¬-]/';
        if (preg_match($specialCharRegex, $file) || preg_match($specialCharRegex, $folder)) {
            return [
                'status' => 'error',
                'message' => 'Filename and folder name must not contain special characters.'
            ];
        }
        // for JSONP filename cannot start with a number
        $fileFirstChar = substr($file, 0, 1);
        // if numeric add 'a' to begining of filename
        if (is_numeric($fileFirstChar)) {
            return [
                'status' => 'error',
                'message' => 'Filenames must not start with a numeric value.'
            ];
        }
        return 'ok';
    }

    public function setImageManager(ImageManager $imageManager)
    {
        $this->imageManager = $imageManager;
    }

    public function getImageManager()
    {
        return $this->imageManager;
    }

    public function setPath(FilesystemInterface $path)
    {
        $this->path = $path;
    }

    public function getPath()
    {
        return $this->path;
    }
}
