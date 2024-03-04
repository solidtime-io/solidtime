<?php

declare(strict_types=1);

$extensions = array_diff(scandir(__DIR__), ['..', '.', '.gitkeep', 'autoload.php', '.DS_Store']);

foreach ($extensions as $extension) {
    $path = __DIR__.DIRECTORY_SEPARATOR.$extension.DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'autoload.php';
    if (file_exists($path)) {
        require_once $path;
    }
}
