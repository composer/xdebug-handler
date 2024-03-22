<?php

declare(strict_types=1);

/*
 * This file is part of composer/xdebug-handler.
 *
 * (c) Composer <https://github.com/composer>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Composer\XdebugHandler\Tests\App\Framework;

use Psr\Log\AbstractLogger;

class Logger extends AbstractLogger
{
    /** @var Output $output */
    private $output;

    public function __construct(Output $output)
    {
        $this->output = $output;
    }

    /**
     * @inheritdoc
     * @phpstan-param mixed[]  $context
     */
    public function log($level, $message, array $context = array()): void
    {
        $this->write((string) $message, Logs::LOGGER_NAME);
    }

    public function write(string $message, string $name): void
    {
        $this->output->write($message, $name);
    }
}
