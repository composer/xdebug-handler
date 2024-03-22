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

/**
 * A plain php script that does not include xdebug-handler
 *
 * Usage: php app-none.php
 */

$app = new AppHelper(__FILE__);

$app->write('start');
$app->writeXdebugStatus();
$app->write('finish');
