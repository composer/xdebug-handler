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

use Psr\Log\AbstractLogger;

/**
 * Psr\Log version 2+ implementation
 */
class Logger extends AbstractLogger
{
    /** @var string[]*/
    protected $output = [];

    /**
     * @inheritdoc
     * @phpstan-param mixed[]  $context
     */
    public function log($level, $message, array $context = []): void
    {
        $this->output[] = $message;
    }

    /**
     * @return string[]
     */
    public function getOutput()
    {
        return $this->output;
    }
}
