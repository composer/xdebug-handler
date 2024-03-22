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

use Composer\XdebugHandler\Tests\App\Helpers\RestarterIni;
use Composer\XdebugHandler\Tests\App\Framework\AppHelper;
use Composer\XdebugHandler\XdebugHandler;

/**
 * Restarts without xdebug with phar.read_only=0, even if xdebug is not loaded
 *
 * Usage: php -dphar.read_only=1 app-extend-ini.php
 */

$app = new AppHelper(__FILE__);

$app->write('start');

$setting = (int) ini_get('phar.readonly');
$app->write('initial phar.readonly='.$setting);

$xdebug = $app->getXdebugHandler(RestarterIni::class);
$xdebug->check();

$app->writeXdebugStatus();
$app->write('finish');
