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

use Composer\XdebugHandler\Helpers\BaseTestCase;
use Composer\XdebugHandler\Mocks\CoreMock;
use Composer\XdebugHandler\Mocks\FailMock;

/**
 * We use PHP_BINARY which only became available in PHP 5.4
 *
 * @requires PHP 5.4
 */
class RestartTest extends BaseTestCase
{
    public function testRestartWhenLoaded()
    {
        $loaded = true;

        $xdebug = CoreMock::createAndCheck($loaded);
        $this->checkRestart($xdebug);
    }

    public function testNoRestartWhenNotLoaded()
    {
        $loaded = false;

        $xdebug = CoreMock::createAndCheck($loaded);
        $this->checkNoRestart($xdebug);
    }

    public function testNoRestartWhenLoadedAndAllowed()
    {
        $loaded = true;
        putenv(CoreMock::ALLOW_XDEBUG.'=1');

        $xdebug = CoreMock::createAndCheck($loaded);
        $this->checkNoRestart($xdebug);
    }

    public function testFailedRestart()
    {
        $loaded = true;

        $xdebug = FailMock::createAndCheck($loaded);
        $this->checkRestart($xdebug);
    }

    public function testRestartWithStdIn()
    {
        $loaded = true;
        $_SERVER['argv'][0] = 'Standard input code';

        $xdebug = CoreMock::createAndCheck($loaded);
        $this->checkRestart($xdebug);
    }

    public function testNoRestartWithCommandLineCode()
    {
        $loaded = true;
        $_SERVER['argv'][0] = '-';

        $xdebug = CoreMock::createAndCheck($loaded);
        $this->checkNoRestart($xdebug);
    }

    public function testNoRestartWithUnreachableScript()
    {
        $loaded = true;
        $_SERVER['argv'][0] = 'nonexistent.php';

        $xdebug = CoreMock::createAndCheck($loaded);
        $this->checkNoRestart($xdebug);
    }

    public function testRestartWithScriptSetter()
    {
        $loaded = true;
        $script = realpath($_SERVER['argv'][0]);
        $_SERVER['argv'][0] = 'nonexistent.php';

        $settings = array('setMainScript' => array($script));

        $xdebug = CoreMock::createAndCheck($loaded, null, $settings);
        $this->checkRestart($xdebug);
    }
}
