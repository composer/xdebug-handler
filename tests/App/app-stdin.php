<?php

/*
 * This file is part of composer/xdebug-handler.
 *
 * (c) Composer <https://github.com/composer>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

require __DIR__.'/../../vendor/autoload.php';

use Composer\XdebugHandler\Tests\App\Framework\AppHelper;
use Composer\XdebugHandler\XdebugHandler;

/**
 * Restarts without xdebug using stdin
 *
 * Usage (Unixy)   : cat app-stdin.php | php -- app-plain.php
 * Usage (Windows) : type app-stdin.php | php -- app-plain.php
 *
 */

$app = new AppHelper(__FILE__);

$app->write('start');

$argv = $app->getServerArgv();
$mainScript = $argv[0];
$app->write('working: argv0 '. $mainScript);

// Set mainScript. Has no effect in restarted process
$settings = null;
if ($mainScript === 'Standard input code' && isset($argv[1])) {
    $settings = ['mainScript' => $argv[1]];
    // @phpstan-ignore-next-line
    unset($_SERVER['argv'][1]);
}

$xdebug = $app->getXdebugHandler(null, $settings);
$xdebug->check();

$app->writeXdebugStatus();
$app->write('finish');
