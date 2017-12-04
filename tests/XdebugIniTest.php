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

use Composer\XdebugHandler\XdebugHandler;
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
        $this->assertEquals($paths, XdebugHandler::getAllIniFiles());
    }

    public function testWithLoadedIniOnly()
    {
        $paths = array(
            'loaded.ini',
        );

        $this->setEnv($paths);
        $this->assertEquals($paths, XdebugHandler::getAllIniFiles());
    }

    public function testWithLoadedIniAndAdditional()
    {
        $paths = array(
            'loaded.ini',
            'one.ini',
            'two.ini',
        );

        $this->setEnv($paths);
        $this->assertEquals($paths, XdebugHandler::getAllIniFiles());
    }

    public function testWithoutLoadedIniAndAdditional()
    {
        $paths = array(
            '',
            'one.ini',
            'two.ini',
        );

        $this->setEnv($paths);
        $this->assertEquals($paths, XdebugHandler::getAllIniFiles());
    }

    public static function setUpBeforeClass()
    {
        // Save current state
        self::$envOriginal = getenv(XdebugHandler::ENV_ORIGINAL);
    }

    public static function tearDownAfterClass()
    {
        // Restore original state
        if (false !== self::$envOriginal) {
            putenv(XdebugHandler::ENV_ORIGINAL.'='.self::$envOriginal);
        } else {
            putenv(XdebugHandler::ENV_ORIGINAL);
        }
    }

    protected function setEnv(array $paths)
    {
        putenv(XdebugHandler::ENV_ORIGINAL.'='.implode(PATH_SEPARATOR, $paths));
    }
}
