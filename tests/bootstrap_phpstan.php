<?php
/**
 * Provides flexibilty for using either simple-phpunit or phpunit
 */

$vendorBin = __DIR__.'/../vendor/bin';

// See if we using simple-phpunit
$path = realpath($vendorBin.'/simple-phpunit');

if ($path !== false) {
    // simple-phpunit will update the .phpunit symlink/junction
    $phpunit = escapeshellarg(PHP_BINARY).' '.escapeshellarg($path);
    passthru($phpunit.' install');

    $autoloader = $vendorBin.'/.phpunit/phpunit/vendor/autoload.php';
    if (!file_exists($autoloader)) {
        echo 'Cannot run PHPStan: simple-phpunit did not install PHPUnit as expected'.PHP_EOL;
        exit(1);
    }

    include $autoloader;
    return;
}

if (realpath($vendorBin.'/phpunit') === false) {
    echo 'Cannot run PHPStan: PHPUnit has not been installed'.PHP_EOL;
    exit(1);
}
