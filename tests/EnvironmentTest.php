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
use Composer\XdebugHandler\Helpers\IniHelper;
use Composer\XdebugHandler\Mocks\CoreMock;
use Composer\XdebugHandler\Mocks\PartialMock;

/**
 * We use PHP_BINARY which only became available in PHP 5.4
 *
 * @requires PHP 5.4
 */
class EnvironmentTest extends BaseTestCase
{
    /**
     * Tests that the _ALLOW_XDEBUG environment variable is correctly formatted
     * for use in the restarted process.
     *
     * @param callable $iniFunc IniHelper method to use
     * @param mixed $scandir Initial value for PHP_INI_SCAN_DIR
     * @param string $expected The _ALLOW_XDEBUG env value
     *
     * @dataProvider envAllowBeforeProvider
     */
    public function testEnvAllowBeforeRestart($iniFunc, $scandir, $expected)
    {
        $ini = new IniHelper();
        call_user_func(array($ini, $iniFunc));
        $this->setScanDir($scandir);

        $loaded = true;
        PartialMock::createAndCheck($loaded);
        $this->assertSame($expected, getenv(CoreMock::ALLOW_XDEBUG));
    }

    public function envAllowBeforeProvider()
    {
        $env = CoreMock::RESTART_ID.'|'.CoreMock::TEST_VERSION.'|';

        // $iniFunc, $scandir, $expected (_ALLOW_XDEBUG value)
        return array(
            'loaded-ini false' => array('setLoadedIni', false, $env.'0'),
            'loaded-ini empty' => array('setLoadedIni', '', $env.'0'),
            'scanned-inis false' => array('setScannedInis', false, $env.'1'),
            'scanned-inis dir' => array('setScannedInis', '/some/where', $env.'1|/some/where'),
        );
    }

    /**
     * Tests that PHP_INI_SCAN_DIR is correctly set so that the restarted
     * process does not scan for additional ini files.
     *
     * @param callable $iniFunc IniHelper method to use
     * @param mixed $scandir Initial value for PHP_INI_SCAN_DIR
     * @param mixed $expected The required PHP_INI_SCAN_DIR value
     *
     * @dataProvider scanDirProvider
     */
    public function testScanDirBeforeRestart($iniFunc, $scandir, $expected)
    {
        $ini = new IniHelper();
        call_user_func(array($ini, $iniFunc));
        $this->setScanDir($scandir);

        $loaded = true;
        PartialMock::createAndCheck($loaded);
        $this->assertSame($expected, getenv('PHP_INI_SCAN_DIR'));
    }

    public function scanDirProvider()
    {
        // $iniFunc, $scandir, $expected (PHP_INI_SCAN_DIR value for restart)
        return array(
            'loaded-ini false' => array('setLoadedIni', false, false),
            'loaded-ini empty' => array('setLoadedIni', '', ''),
            'scanned-inis false' => array('setScannedInis', false, ''),
            'scanned-inis dir' => array('setScannedInis', '/some/where', ''),
        );
    }

    /**
     * Tests that PHP_INI_SCAN_DIR is restored to its original value after the
     * process has been restarted. Also tests that getRestartSettings reports
     * correct values.
     *
     * @param callable $iniFunc IniHelper method to use
     * @param mixed $scandir Initial value for PHP_INI_SCAN_DIR
     *
     * @dataProvider scanDirProvider
     */
    public function testScanDirAfterRestart($iniFunc, $scandir)
    {
        $ini = new IniHelper();
        call_user_func(array($ini, $iniFunc));
        $this->setScanDir($scandir);

        $loaded = true;
        $xdebug = CoreMock::createAndCheck($loaded);

        $this->checkRestart($xdebug);
        $this->assertSame($scandir, getenv('PHP_INI_SCAN_DIR'));

        // Check that $_SERVER has been updated
        if (false !== $scandir) {
            $this->assertSame($scandir, $_SERVER['PHP_INI_SCAN_DIR']);
        } else {
            $this->assertSame(false, isset($_SERVER['PHP_INI_SCAN_DIR']));
        }

        // Check that restart settings reports original scan dir
        $settings = CoreMock::getRestartSettings();
        $this->assertSame($scandir, $settings['scanDir']);

        // Check that restart settings reports scannedInis
        $scannedInis = $iniFunc === 'setScannedInis';
        $this->assertSame($scannedInis, $settings['scannedInis']);
    }

    private function setScanDir($value)
    {
        if (false !== $value) {
            putenv('PHP_INI_SCAN_DIR='.$value);
            $_SERVER['PHP_INI_SCAN_DIR'] = $value;
        } else {
            putenv('PHP_INI_SCAN_DIR');
            unset($_SERVER['PHP_INI_SCAN_DIR']);
        }
    }
}
