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
use Composer\XdebugHandler\Tests\App\Framework\PhpOptions;
use Composer\XdebugHandler\XdebugHandler;
use Composer\XdebugHandler\PhpConfig;

/**
 * Restarts without xdebug in standard mode, then calls sub-processes:
 * - app-plain.php (xdebug will be loaded)
 * - app-basic.php with standard settings (xdebug not loaded)
 *
 * Usage: php app-standard.php
 */

$app = new AppHelper(__FILE__);

$app->write('start');

$xdebug = $app->getXdebugHandler(null);
$xdebug->check();

$app->writeXdebugStatus();

// Simple call to app-plain (xdebug will be loaded)
$app->write('CALL app-plain.php');
$app->write('- if we are in a restart, xdebug will be loaded');
$app->runScript('app-plain.php');

// app-basic with xdebug
$app->write('CALL app-basic.php with standard settings');
$app->write('- if we are in a restart, xdebug should not be loaded');

$phpConfig = new PhpConfig();
$args = $phpConfig->useStandard();
$options = new PhpOptions();
$options->addPhpArgs(...$args);

$app->runScript('app-basic.php', $options);
$app->write('finish');
