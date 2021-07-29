<?php

/*
 * This file is part of composer/xdebug-handler.
 *
 * (c) Composer <https://github.com/composer>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Composer\XdebugHandler\Helpers;

/**
 * Required to provide the correct Psr\Log implementation for phpstan analysis
 */
class LoggerFactory
{
    public static function createLogger()
    {
        $class = PHP_VERSION_ID < 70100 ? 'LegacyLogger' : 'Logger';
        $className = __NAMESPACE__.'\\'.$class;

        return new $className();
    }
}
