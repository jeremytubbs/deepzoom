Deepzoom
==
Tile Generator for use with OpenSeadragon.

https://openseadragon.github.io/

Example implementation -
```php
  // Setup Deepzoom
  $deepzoom = Jeremytubbs\Deepzoom\DeepzoomFactory::create([
      'path' => 'images', // Export path for tiles
      'driver' => 'imagick', // Choose between gd and imagick support.
      'format' => 'jpg',
  ]);
  // folder, file are optional and will default to filename
  $response = $deepzoom->makeTiles('KISS.jpg', 'file', 'folder');
```

Example response -
```javascript
{
  status: "ok",
  data: {
    output: {
      JSONP: "folder/file.js",
      DZI: "folder/file.dzi",
      _files: "folder/file_files"
    },
    source: "source/file/path"
  },
  message: "Everything is okay!"
}
```

### Supported Image Libraries
- GD Library (>=2.0)
- Imagick PHP extension (>=6.5.7)

### FYI:
Filenames for JSONP must not start with a number and should not contain hyphen therefore filename spaces and hyphens will be converted to underscores. Folder name spaces will be converted to hyphens. If you would like to avoid this auto-naming declare your 'folder' and 'file' within the maketiles method. 

