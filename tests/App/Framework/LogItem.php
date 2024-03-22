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

class LogItem
{
    /** @var int */
    public $pid;
    /** @var string */
    public $name;
    /** @var string */
    public $value;

    public function __construct(int $pid, string $name, string $value)
    {
        $this->pid = $pid;
        $this->name = $name;
        $this->value = $value;
    }
}
