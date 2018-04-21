<?php

/*
 * This file is part of composer/xdebug-handler.
 *
 * (c) Composer <https://github.com/composer>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Composer\XdebugHandler\Mocks;

use Composer\XdebugHandler\Process;

/**
 * CommandMock captures the command used for the restart and makes it publically
 * accessible.
 */
class CommandMock extends CoreMock
{
    public $command;

    protected function restart($command)
    {
        $this->command = $command;
        parent::restart($command);
    }
}
