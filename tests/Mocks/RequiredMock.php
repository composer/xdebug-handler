<?php

/*
 * This file is part of composer/xdebug-handler.
 *
 * (c) Composer <https://github.com/composer>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Composer\XdebugHandler\Tests\Mocks;

/**
 * RequiredMock provides the runCoreMock method to set the $required property
 * and then calls the parent createAndCheck method.
 */
class RequiredMock extends CoreMock
{
    /** @var bool */
    protected static $required;

    /**
     * @param bool $loaded
     * @param bool $required
     *
     * @return \Composer\XdebugHandler\Tests\Mocks\CoreMock
     */
    public static function runCoreMock($loaded, $required)
    {
        static::$required = $required;
        return parent::createAndCheck($loaded, null);
    }

    /**
     * @param bool $isLoaded
     */
    protected function requiresRestart($isLoaded)
    {
        return $isLoaded && static::$required;
    }
}
