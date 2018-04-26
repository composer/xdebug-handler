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
use Composer\XdebugHandler\Mocks\CommandMock;
use Composer\XdebugHandler\Mocks\CoreMock;
use Composer\XdebugHandler\Mocks\FailMock;
use Composer\XdebugHandler\Mocks\RequiredMock;
use Composer\XdebugHandler\Process;

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

    /**
     * @dataProvider unreachableScriptProvider
     */
    public function testNoRestartWithUnreachableScript($script)
    {
        $loaded = true;
        // We can only check this by setting a script
        $settings = array('setMainScript' => array($script));

        $xdebug = CoreMock::createAndCheck($loaded, null, $settings);
        $this->checkNoRestart($xdebug);
    }

    public function unreachableScriptProvider()
    {
        return array(
            array('nonexistent.php'),
            array('-'),
            array('Standard input code'),
        );
    }

    /**
     * @dataProvider scriptSetterProvider
     */
    public function testRestartWithScriptSetter($script)
    {
        $loaded = true;
        $settings = array('setMainScript' => array($script));

        $xdebug = CommandMock::createAndCheck($loaded, null, $settings);
        $this->checkRestart($xdebug);

        $escaped = Process::escape($script);
        $this->assertContains(" {$escaped} ", " {$xdebug->command} ");
    }

    public function scriptSetterProvider()
    {
        return array(
            array(realpath($_SERVER['argv'][0])),
            array('--'),
        );
    }

    public function testNoRestartWhenNotRequired()
    {
        $loaded = true;
        $required = false;

        $xdebug = RequiredMock::runCoreMock($loaded, $required);
        $this->checkNoRestart($xdebug);
    }

    public function testNoRestartWhenRequiredAndAllowed()
    {
        $loaded = true;
        putenv(CoreMock::ALLOW_XDEBUG.'=1');
        $required = true;

        $xdebug = RequiredMock::runCoreMock($loaded, $required);
        $this->checkNoRestart($xdebug);
    }
}
