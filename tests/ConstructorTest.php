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

use PHPUnit\Framework\TestCase;

/**
 * This class does not need to extend Helpers\BaseTestCase
 */
class ConstructorTest extends TestCase
{
    /**
     * @expectedException \RuntimeException
     *
     */
    public function testThrowsOnEmptyEnvPrefix()
    {
        new XdebugHandler('');
    }

    /**
     * @expectedException \RuntimeException
     *
     */
    public function testThrowsOnInvalidEnvPrefix()
    {
        new XdebugHandler(array('name'));
    }

    /**
     * @expectedException \RuntimeException
     *
     */
    public function testThrowsOnInvalidColorOption()
    {
        new XdebugHandler('test', false);
    }
}
