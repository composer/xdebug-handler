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

use Composer\XdebugHandler\PhpConfig;
use Composer\XdebugHandler\Tests\Helpers\BaseTestCase;
use Composer\XdebugHandler\Tests\Helpers\EnvHelper;
use Composer\XdebugHandler\Tests\Mocks\CoreMock;

/**
 * We use PHP_BINARY which only became available in PHP 5.4
 *
 * @requires PHP 5.4
 */
class PhpConfigTest extends BaseTestCase
{
    /**
     * Tests that the correct command-line options are returned.
     *
     * @param string $method PhpConfig method to call
     * @param string[] $expected
     * @dataProvider commandLineProvider
     */
    public function testCommandLineOptions($method, $expected)
    {
        $loaded = true;
        CoreMock::createAndCheck($loaded);
        $settings = CoreMock::getRestartSettings();

        if (null === $settings) {
            $this->fail('getRestartSettings returned null');
        }

        $config = new PhpConfig();
        $options = BaseTestCase::safeCall($config, $method, null, $this);

        if ($method === 'useStandard') {
            $expected[2] = $settings['tmpIni'];
        }

        $this->assertSame($expected, $options);
    }

    public function commandLineProvider()
    {
        // $method, $expected
        return array(
            'original' => array('useOriginal', array()),
            'standard' => array('useStandard', array('-n', '-c', '')),
            'persistent' => array('usePersistent', array()),
        );
    }

    /**
     * Tests that the environment is set correctly for each mode.
     *
     * @param string $iniFunc IniHelper method to use
     * @param false|string $scanDir Initial value for PHP_INI_SCAN_DIR
     * @param false|string $phprc Initial value for PHPRC
     * @dataProvider environmentProvider
     */
    public function testEnvironment($iniFunc, $scanDir, $phprc)
    {
        if ($message = EnvHelper::shouldSkipTest($scanDir)) {
            $this->markTestSkipped($message);
        }

        $ini = EnvHelper::setInis($iniFunc, $scanDir, $phprc);

        $loaded = true;
        CoreMock::createAndCheck($loaded);
        $settings = CoreMock::getRestartSettings();

        if (null === $settings) {
            $this->fail('getRestartSettings returned null');
        }

        $config = new PhpConfig();
        $tests = array('useOriginal', 'usePersistent', 'useStandard');

        foreach ($tests as $method) {
            BaseTestCase::safeCall($config, $method, null, $this);

            if ($method === 'usePersistent') {
                $expectedScanDir = '';
                $expectedPhprc = $settings['tmpIni'];
            } else {
                $expectedScanDir = $scanDir;
                $expectedPhprc = $phprc;
            }

            $this->checkEnvironment($expectedScanDir, $expectedPhprc, $method);
        }
    }

    public function environmentProvider()
    {
        return EnvHelper::dataProvider();
    }

    /**
     * Checks the value of variables in the local environment and $_SERVER
     *
     * @param mixed $scanDir
     * @param mixed $phprc
     * @param string $name
     *
     * @return void
     */
    private function checkEnvironment($scanDir, $phprc, $name)
    {
        $tests = array('PHP_INI_SCAN_DIR' => $scanDir, 'PHPRC' => $phprc);

        foreach ($tests as $env => $value) {
            $message = $name.' '.strtolower($env);
            $this->assertSame($value, getenv($env), 'getenv '.$message);

            if (false === $value) {
                $this->assertSame($value, isset($_SERVER[$env]), '$_SERVER '.$message);
            } else {
                $this->assertSame($value, $_SERVER[$env], '$_SERVER '.$message);
            }
        }
    }
}
