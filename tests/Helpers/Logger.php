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

use Psr\Log\AbstractLogger;

class Logger extends AbstractLogger
{
    protected $output = array();

    public function log($level, $message, array $context = array())
    {
        $this->output[] = array($level, $message, $context);
    }

    public function getOutput()
    {
        return $this->output;
    }
}
