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

use Composer\XdebugHandler\XdebugHandlerMock;
use PHPUnit\Framework\TestCase;

class IniHelperTest extends TestCase
{
    public static $envOriginal;

    public function testWithNoIni()
    {
        $paths = array(
            '',
        );

        $this->setEnv($paths);
        $this->assertEquals($paths, XdebugHandlerMock::getAllIniFiles());
    }

    public function testWithLoadedIniOnly()
    {
        $paths = array(
            'loaded.ini',
        );

        $this->setEnv($paths);
        $this->assertEquals($paths, XdebugHandlerMock::getAllIniFiles());
    }

    public function testWithLoadedIniAndAdditional()
    {
        $paths = array(
            'loaded.ini',
            'one.ini',
            'two.ini',
        );

        $this->setEnv($paths);
        $this->assertEquals($paths, XdebugHandlerMock::getAllIniFiles());
    }

    public function testWithoutLoadedIniAndAdditional()
    {
        $paths = array(
            '',
            'one.ini',
            'two.ini',
        );

        $this->setEnv($paths);
        $this->assertEquals($paths, XdebugHandlerMock::getAllIniFiles());
    }

    public static function setUpBeforeClass()
    {
        // Save current state
        self::$envOriginal = getenv(XdebugHandlerMock::ORIGINAL_INIS);

        // Create a new mock object so that the static $name variable is set
        $xdebug = new XdebugHandlerMock(true);
    }

    public static function tearDownAfterClass()
    {
        // Restore original state
        if (false !== self::$envOriginal) {
            putenv(XdebugHandlerMock::ORIGINAL_INIS.'='.self::$envOriginal);
        } else {
            putenv(XdebugHandlerMock::ORIGINAL_INIS);
        }
    }

    protected function setEnv(array $paths)
    {
        putenv(XdebugHandlerMock::ORIGINAL_INIS.'='.implode(PATH_SEPARATOR, $paths));
    }
}
