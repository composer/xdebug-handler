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
 * Restarts without xdebug using an autoprepend file
 *
 * Usage: php -dauto_prepend_file=app-prepend.php app-plain.php
 *
 * The auto_prepend_file ini setting from the command-line is picked up when the
 * ini content is merged, so it is available in the restart.
 *
 * If stdin is used, it will be read in the restart:
 * Usage (Unixy)   : cat app-plain.php | php -dauto_prepend_file=app-prepend.php
 * Usage (Windows) : type app-plain.php | php -dauto_prepend_file=app-prepend.php
 */

$app = new AppHelper(__FILE__);

$app->write('start');

$mainScript = $app->getServerArgv0();
$app->write('initial argv0 '.$mainScript);

// Set mainScript. Has no effect in restarted process
$settings = null;
if ($mainScript === 'Standard input code') {
    $settings = ['mainScript' => '--'];
}

$xdebug = $app->getXdebugHandler(null, $settings);
$xdebug->check();

$app->writeXdebugStatus();
$app->write('finish');
