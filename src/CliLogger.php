<?php

/*
 * This file is part of composer/xdebug-handler.
 *
 * (c) Composer <https://github.com/composer>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Composer\XdebugHandler;

use Psr\Log\AbstractLogger;

/**
 * Writes XdebugHandler status messages to stdout in a CLI process.
 *
 * @author John Stevenson <john-stevenson@blueyonder.co.uk>
 */
class CliLogger extends AbstractLogger
{
    private $cli;

    public function __construct()
    {
        $this->cli = PHP_SAPI === 'cli';
    }

    public function log($level, $message, array $context = array())
    {
        if ($this->cli) {
            print($message.PHP_EOL);
        }
    }
}
