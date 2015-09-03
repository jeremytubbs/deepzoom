Deepzoom
--
Tile Generator for use with OpenSeadragon.

https://openseadragon.github.io/

```php
  // Setup Deepzoom 
  $deepzoom = Jeremytubbs\Deepzoom\DeepzoomFactory::create([
      'path' => 'images', // Export path for tiles
      'driver' => 'imagick', // Image driver
  ]);
  // folder, file are optional and will default to filename
  $deepzoom->makeTiles('folder', 'file', 'KISS.jpg');
```

