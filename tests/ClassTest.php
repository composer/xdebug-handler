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

use Composer\XdebugHandler\Helpers\Logger;
use PHPUnit\Framework\TestCase;

/**
 * This class does not need to extend Helpers\BaseTestCase
 */
class ClassTest extends TestCase
{
    /**
     * @expectedException \RuntimeException
     */
    public function testConstructorThrowsOnEmptyEnvPrefix()
    {
        new XdebugHandler('');
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testConstructorThrowsOnInvalidEnvPrefix()
    {
        new XdebugHandler(array('name'));
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testConstructorThrowsOnInvalidColorOption()
    {
        new XdebugHandler('test', false);
    }

    /**
     * @dataProvider setterProvider
     */
    public function testSettersAreFluent($setter, $value)
    {
        $xdebug = new XdebugHandler('myapp');

        $params = null !== $value ? array($value) : array();
        $result = call_user_func_array(array($xdebug, $setter), $params);
        $this->assertInstanceOf(get_class($xdebug), $result);
    }

    public function setterProvider()
    {
        // $setter, $value
        return array(
            'setLogger' => array('setLogger', new Logger()),
            'setMainScript' => array('setMainScript', '--'),
            'setPersistent' => array('setPersistent', null),
        );
    }
}
