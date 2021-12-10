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
use Composer\XdebugHandler\Tests\Helpers\Logger;
use Composer\XdebugHandler\XdebugHandler;

class ClassTest extends BaseTestCase
{
    /**
     * @return void
     */
    public function testConstructorThrowsOnEmptyEnvPrefix()
    {
        $this->expectException('RuntimeException');
        new XdebugHandler('');
    }

    /**
     * @return void
     */
    public function testConstructorThrowsOnInvalidEnvPrefix()
    {
        $this->expectException('RuntimeException');
        /** @phpstan-ignore-next-line */
        new XdebugHandler(['name']);
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

        $params = null !== $value ? [$value] : [];
        $result = BaseTestCase::safeCall($xdebug, $setter, $params, $this);
        self::assertInstanceOf(get_class($xdebug), $result);
    }

    /**
     * @return array<string, mixed[]>
     */
    public function setterProvider()
    {
        // $setter, $value
        return [
            'setLogger' => ['setLogger', new Logger()],
            'setMainScript' => ['setMainScript', '--'],
            'setPersistent' => ['setPersistent', null],
        ];
    }

    /**
     * Test compatibility with 1.x for extending classes
     *
     * @dataProvider methodProvider
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
        return [
            ['requiresRestart'],
            ['restart'],
        ];
    }
}
