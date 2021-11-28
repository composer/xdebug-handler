<?php

/*
 * This file is part of composer/xdebug-handler.
 *
 * (c) Composer <https://github.com/composer>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Composer\XdebugHandler\Tests\Helpers;

/**
 * Required to provide the correct Psr\Log implementation for phpstan analysis
 */
class LoggerFactory
{
    /**
     * @return \Composer\XdebugHandler\Tests\Helpers\Logger
     */
    public static function createLogger()
    {
        if (PHP_VERSION_ID < 70100) {
            /** @phpstan-ignore-next-line */
            return new LegacyLogger();
        }

        return new Logger();
    }
}
