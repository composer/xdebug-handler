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

/**
 * FailMock provides its own restart method that mocks a restart with Xdebug
 * still loaded.
 */
class FailMock extends CoreMock
{
    protected function restart($command)
    {
        static::createAndCheck(true, $this, static::$settings);
    }
}
