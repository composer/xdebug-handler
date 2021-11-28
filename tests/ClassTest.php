<?php

/*
 * This file is part of composer/xdebug-handler.
 *
 * (c) Composer <https://github.com/composer>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Composer\XdebugHandler\Tests;

use Composer\XdebugHandler\Tests\Helpers\LoggerFactory;
use Composer\XdebugHandler\XdebugHandler;
use PHPUnit\Framework\TestCase;

/**
 * This class does not need to extend Helpers\BaseTestCase
 */
class ClassTest extends TestCase
{
    public function testConstructorThrowsOnEmptyEnvPrefix()
    {
        $this->setException('RuntimeException');
        new XdebugHandler('');
    }

    public function testConstructorThrowsOnInvalidEnvPrefix()
    {
        $this->setException('RuntimeException');
        /** @phpstan-ignore-next-line */
        new XdebugHandler(array('name'));
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
            'setLogger' => array('setLogger', LoggerFactory::createLogger()),
            'setMainScript' => array('setMainScript', '--'),
            'setPersistent' => array('setPersistent', null),
        );
    }

    /**
     * Test compatibility with 1.x for extending classes
     *
     * @requires PHP 7.1
     * @dataProvider methodProvider
     */
    public function testNoTypeHintingOnMethod($method)
    {
        $xdebug = new XdebugHandler('myapp');
        $refMethod = new \ReflectionMethod($xdebug, $method);
        $refParams = $refMethod->getParameters();

        $this->assertCount(1, $refParams);
        $this->assertNull($refParams[0]->getType());
    }

    public function methodProvider()
    {
        return array(
            array('requiresRestart'),
            array('restart'),
        );
    }

    private function setException($exception)
    {
        if (!method_exists($this, 'expectException')) {
            /** @phpstan-ignore-next-line */
            $this->setExpectedException($exception);
        } else {
            $this->expectException($exception);
        }
    }
}
