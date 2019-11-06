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
use Composer\XdebugHandler\Mocks\PartialMock;
use Composer\XdebugHandler\Mocks\RequiredMock;

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
        $this->setArgv();

        $xdebug = CoreMock::createAndCheck($loaded);
        $this->checkRestart($xdebug);

        // Check command
        $xdebug = PartialMock::createAndCheck($loaded);
        $command = $xdebug->getCommand();
        $n = Process::escape('-n');
        $c = Process::escape('-c');
        $tmpIni = Process::escape($xdebug->getTmpIni());

        $pattern = preg_quote(sprintf('%s %s %s', $n, $c, $tmpIni), '/');
        $this->assertRegExp('/'.$pattern.'/', $command);
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
        $this->setArgv();
        $settings = array('setMainScript' => array($script));

        $xdebug = CoreMock::createAndCheck($loaded, null, $settings);
        $this->checkRestart($xdebug);

        // Check command
        $xdebug = PartialMock::createAndCheck($loaded, null, $settings);
        $command = $xdebug->getCommand();

        $pattern = preg_quote(Process::escape($script), '/');
        $this->assertRegExp('/'.$pattern.'/', $command);
    }

    public function scriptSetterProvider()
    {
        return array(
            array(realpath($_SERVER['argv'][0])),
            array('--'),
        );
    }

    public function testSetPersistent()
    {
        $loaded = true;
        $this->setArgv();
        $settings = array('setPersistent' => array());

        // Check command
        $xdebug = PartialMock::createAndCheck($loaded, null, $settings);
        $command = $xdebug->getCommand();
        $tmpIni = $xdebug->getTmpIni();

        foreach (array('-n', '-c', $tmpIni) as $param) {
            $pattern = preg_quote(Process::escape($param), '/');
            $matched = (bool) preg_match('/'.$pattern.'/', $command);
            $this->assertFalse($matched);
        }
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

    /**
     * Sets $_SERVER['argv'] for testing commands
     */
    private function setArgv()
    {
        $_SERVER['argv'] = array(__FILE__, 'command', '--param1, --param2');
    }
}
