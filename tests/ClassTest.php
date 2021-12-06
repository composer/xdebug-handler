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

use Composer\XdebugHandler\Tests\Helpers\BaseTestCase;
use Composer\XdebugHandler\Tests\Helpers\LoggerFactory;
use Composer\XdebugHandler\XdebugHandler;

class ClassTest extends BaseTestCase
{
    /**
     * @return void
     */
    public function testConstructorThrowsOnEmptyEnvPrefix()
    {
        $this->setException('RuntimeException');
        new XdebugHandler('');
    }

    /**
     * @return void
     */
    public function testConstructorThrowsOnInvalidEnvPrefix()
    {
        $this->setException('RuntimeException');
        /** @phpstan-ignore-next-line */
        new XdebugHandler(array('name'));
    }

    /**
     * @dataProvider setterProvider     *
     * @param string $setter
     * @param \Psr\Log\AbstractLogger|string|null $value
     *
     * @return void
     */
    public function testSettersAreFluent($setter, $value)
    {
        $xdebug = new XdebugHandler('myapp');

        $params = null !== $value ? array($value) : array();
        $result = BaseTestCase::safeCall($xdebug, $setter, $params, $this);
        self::assertInstanceOf(get_class($xdebug), $result);
    }

    /**
     * @return array<string, mixed[]>
     */
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
     * @dataProvider methodProvider     *
     * @param string $method
     *
     * @return void
     */
    public function testNoTypeHintingOnMethod($method)
    {
        $xdebug = new XdebugHandler('myapp');
        $refMethod = new \ReflectionMethod($xdebug, $method);
        $refParams = $refMethod->getParameters();

        self::assertCount(1, $refParams);
        self::assertNull($refParams[0]->getType());
    }

    /**
     * @return array<string[]>
     */
    public function methodProvider()
    {
        return array(
            array('requiresRestart'),
            array('restart'),
        );
    }

    /**
     * @param string $exception
     * @phpstan-param class-string<\Exception> $exception
     *
     * @return void
     */
    private function setException($exception)
    {
        if (method_exists($this, 'expectException')) {
            $this->expectException($exception);
        } else {
            /** @phpstan-ignore-next-line */
            $this->setExpectedException($exception);
        }
    }
}
