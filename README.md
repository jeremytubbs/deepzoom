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
  ]);
  // folder, file are optional and will default to filename
  $response = $deepzoom->makeTiles('KISS.jpg', 'file', 'folder');
```

Example response -
```javascript
{
  status: "ok",
  data: {
    JSONP: "folder/file.js",
    DZI: "folder/file.dzi",
    _files: "folder/file_files"
  },
  message: "Everything is okay!"
}
```

### Supported Image Libraries
- GD Library (>=2.0)
- Imagick PHP extension (>=6.5.7)

### FYI:
Filenames must be non-numeric and should not contain underscores, hyphens or spaces.

