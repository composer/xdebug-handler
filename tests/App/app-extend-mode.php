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

use Composer\XdebugHandler\Tests\App\Helpers\RestarterMode;
use Composer\XdebugHandler\Tests\App\Framework\AppHelper;
use Composer\XdebugHandler\XdebugHandler;

/**
 * Restarts with xdebug and xdebug.mode=coverage
 *
 * Usage: php -dxdebug.mode=develop app-extend-mode.php
 */

$app = new AppHelper(__FILE__);

$app->write('start');

$xdebug = $app->getXdebugHandler(RestarterMode::class);
$xdebug->check();

$app->writeXdebugStatus();
$app->write('finish');
