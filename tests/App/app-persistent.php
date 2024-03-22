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
use Composer\XdebugHandler\PhpConfig;

/**
 * Restarts without xdebug in persistent mode, then calls sub-processes:
 * - app-plain.php (xdebug not loaded)
 * - app-plain.php with original settings (xdebug will be loaded)
 * - app-other.php (xdebug not loaded)
 *
 * Usage: php app-persistent.php
 */

$app = new AppHelper(__FILE__);

$app->write('start');

$xdebug = $app->getXdebugHandler(null, ['persistent' => '']);
$xdebug->check();

$app->writeXdebugStatus();

// Simple call to app-plain (xdebug should not be loaded)
$app->write('CALL app-plain.php');
$app->write('- if we are in a restart, xdebug should not be loaded');
$app->runScript('app-plain.php');

// app-plain with xdebug
$app->write('CALL app-plain.php with original settings');
$app->write('- if we are in a restart, xdebug will be loaded');

$phpConfig = new PhpConfig;
$phpConfig->useOriginal();
$app->runScript('app-plain.php');

// restore persistent settings
$phpConfig->usePersistent();

// Simple call to app-basic (xdebug should not be loaded)
$app->write('CALL app-basic.php');
$app->write('- if we are in a restart, xdebug should not be loaded');
$app->runScript('app-basic.php');

$app->write('finish');
