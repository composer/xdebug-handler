<?php

$vendorBin = __DIR__.'/../vendor/bin';
$path = realpath($vendorBin.'/simple-phpunit');

if (!file_exists($vendorBin.'/phpunit') && $path !== false) {
    passthru(escapeshellarg($path).' install');

    $autoloader = $vendorBin.'/.phpunit/phpunit/vendor/autoload.php';
    if (file_exists($autoloader)) {
        require $autoloader;
    }
}
