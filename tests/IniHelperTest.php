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

use Composer\XdebugHandler\Helpers\CoreMock;
use Composer\XdebugHandler\Helpers\TestCase;

class IniHelperTest extends TestCase
{
    public function testWithNoIni()
    {
        $paths = array(
            '',
        );

        $this->setEnv($paths);
        $this->assertEquals($paths, CoreMock::getAllIniFiles());
    }

    public function testWithLoadedIniOnly()
    {
        $paths = array(
            'loaded.ini',
        );

        $this->setEnv($paths);
        $this->assertEquals($paths, CoreMock::getAllIniFiles());
    }

    public function testWithLoadedIniAndAdditional()
    {
        $paths = array(
            'loaded.ini',
            'one.ini',
            'two.ini',
        );

        $this->setEnv($paths);
        $this->assertEquals($paths, CoreMock::getAllIniFiles());
    }

    public function testWithoutLoadedIniAndAdditional()
    {
        $paths = array(
            '',
            'one.ini',
            'two.ini',
        );

        $this->setEnv($paths);
        $this->assertEquals($paths, CoreMock::getAllIniFiles());
    }

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        // Create a new mock object so that the static $name variable is set
        $xdebug = CoreMock::createAndCheck(true);
    }

    protected function setEnv(array $paths)
    {
        putenv(CoreMock::ORIGINAL_INIS.'='.implode(PATH_SEPARATOR, $paths));
    }
}
