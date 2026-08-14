<?php
$baseDir = __DIR__;

if (file_exists($baseDir . '/autoload.php')) {
  require_once $baseDir . '/autoload.php';
}

$eventFiles = [
  '/events/custom_types.php',
  '/events/media.php',
  '/events/cache.php',
  '/events/iblock.php',
  '/events/fileman.php'
];

foreach ($eventFiles as $file) {
  if (file_exists($baseDir . $file)) {
    require_once $baseDir . $file;
  }
}
