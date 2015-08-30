<?php

namespace Jeremytubbs\Deepzoom;

use Intervention\Image\Image;

class Deepzoom
{

    private $tileSize;
    private $tileOverlap;
    private $tileFormat;

    public function __construct()
    {
        $this->tileSize = 256;
        $this->tileOverlap = 1;
        $this->tileFormat = 'jpg';
    }

    function test() {
        return 'test';
    }

}
