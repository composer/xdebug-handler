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

use Composer\Pcre\Preg;
use Composer\XdebugHandler\Tests\Helpers\BaseTestCase;
use Composer\XdebugHandler\Tests\Mocks\CoreMock;
use Composer\XdebugHandler\Tests\Mocks\FailMock;
use Composer\XdebugHandler\Tests\Mocks\PartialMock;
use Composer\XdebugHandler\Tests\Mocks\RequiredMock;

class RestartTest extends BaseTestCase
{
    /**
     * @return void
     */
    public function testRestartWhenLoaded()
    {
        $loaded = true;
        $this->setArgv();

        $xdebug = CoreMock::createAndCheck($loaded);
        $this->checkRestart($xdebug);

        // Check command
        $xdebug = PartialMock::createAndCheck($loaded);
        $command = implode(' ', $xdebug->getCommand());
        $tmpIni = $xdebug->getTmpIni();

        $pattern = preg_quote(sprintf(' -n -c %s ', $tmpIni), '/');
        $matched = Preg::isMatch('/'.$pattern.'/', $command);
        self::assertTrue($matched);
    }

    /**
     * @return void
     */
    public function testRestartWhenModeIsNotOff()
    {
        $loaded = array(true, 'debug,trace');

        $xdebug = CoreMock::createAndCheck($loaded);
        $this->checkRestart($xdebug);
        self::assertFalse($xdebug::isXdebugActive());
    }

    /**
     * @return void
     */
    public function testNoRestartWhenNotLoaded()
    {
        $loaded = false;

        $xdebug = CoreMock::createAndCheck($loaded);
        $this->checkNoRestart($xdebug);
        self::assertFalse($xdebug::isXdebugActive());
    }

    /**
     * @return void
     */
    public function testNoRestartWhenLoadedAndAllowed()
    {
        $loaded = true;
        putenv(CoreMock::ALLOW_XDEBUG.'=1');

        $xdebug = CoreMock::createAndCheck($loaded);
        $this->checkNoRestart($xdebug);
        self::assertTrue($xdebug::isXdebugActive());
    }

    /**
     * @return void
     */
    public function testNoRestartWhenModeIsOff()
    {
        $loaded = array(true, 'off');

        $xdebug = CoreMock::createAndCheck($loaded);
        $this->checkNoRestart($xdebug);
        self::assertFalse($xdebug::isXdebugActive());
    }

    /**
     * @return void
     */
    public function testFailedRestart()
    {
        $loaded = true;

        $xdebug = FailMock::createAndCheck($loaded);
        $this->checkRestart($xdebug);
    }

    /**
     * @dataProvider unreachableScriptProvider     *
     * @param string $script
     *
     * @return void
     */
    public function testNoRestartWithUnreachableScript($script)
    {
        $loaded = true;
        // We can only check this by setting a script
        $settings = array('setMainScript' => array($script));

        $xdebug = CoreMock::createAndCheck($loaded, null, $settings);
        $this->checkNoRestart($xdebug);
    }

    /**
     * @return array<string[]>
     */
    public function unreachableScriptProvider()
    {
        return array(
            array('nonexistent.php'),
            array('-'),
            array('Standard input code'),
        );
    }

    /**
     * @dataProvider scriptSetterProvider     *
     * @param string $script
     *
     * @return void
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

        self::assertContains($script, $command);
    }

    /**
     * @return array<string[]>
     */
    public function scriptSetterProvider()
    {
        return array(
            array((string) realpath($_SERVER['argv'][0])),
            array('--'),
        );
    }

    /**
     * @return void
     */
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
            self::assertNotContains($param, $command);
        }
    }

    /**
     * @return void
     */
    public function testNoRestartWhenNotRequired()
    {
        $loaded = true;
        $required = false;

        $xdebug = RequiredMock::runCoreMock($loaded, $required);
        $this->checkNoRestart($xdebug);
    }

    /**
     * @return void
     */
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
     *
     * @return void
     */
    private function setArgv()
    {
        $_SERVER['argv'] = array(__FILE__, 'command', '--param1, --param2');
    }
}
