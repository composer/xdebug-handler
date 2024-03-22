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

use Composer\XdebugHandler\Tests\App\Helpers\RestarterAllow;
use Composer\XdebugHandler\Tests\App\Framework\AppHelper;
use Composer\XdebugHandler\XdebugHandler;

/**
 * No restart if a help command (-h, --help, help)
 *
 * Usage: php app-extend-help.php -h
 *
 */

$app = new AppHelper(__FILE__);

$app->write('start');

$xdebug = $app->getXdebugHandler(RestarterAllow::class);
$xdebug->check();

$app->writeXdebugStatus();
$app->write('finish');
