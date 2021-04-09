<?php

$vendorBin = __DIR__.'/../vendor/bin';
$path = $vendorBin.'/simple-phpunit';

if (!file_exists($vendorBin.'/phpunit') && file_exists($path)) {
    passthru(escapeshellarg(realpath($path)).' install');

    $autoloader = $vendorBin.'/.phpunit/phpunit/vendor/autoload.php';
    if (file_exists($autoloader)) {
        require $autoloader;
    }
}
