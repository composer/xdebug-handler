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
 * RequiredMock provides the runCoreMock method to set the $required property
 * and then calls the parent createAndCheck method.
 */
class RequiredMock extends CoreMock
{
    protected static $required;

    public static function runCoreMock($loaded, $required)
    {
        static::$required = $required;
        return parent::createAndCheck($loaded, null);
    }

    protected function requiresRestart($isLoaded)
    {
        return $isLoaded && static::$required;
    }
}
