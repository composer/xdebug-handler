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
 * @phpstan-import-type envTestData from EnvHelper
 */
class PhpConfigTest extends BaseTestCase
{
    /**
     * Tests that the correct command-line options are returned.
     *
     * @param string $method PhpConfig method to call
     * @param string[] $expected
     * @dataProvider commandLineProvider
     *
     * @return void
     */
    public function testCommandLineOptions($method, $expected)
    {
        $loaded = true;
        CoreMock::createAndCheck($loaded);
        $settings = CoreMock::getRestartSettings();

        if (null === $settings) {
            self::fail('getRestartSettings returned null');
        }

        $config = new PhpConfig();
        $options = BaseTestCase::safeCall($config, $method, null, $this);

        if ($method === 'useStandard') {
            $expected[2] = $settings['tmpIni'];
        }

        self::assertSame($expected, $options);
    }

    /**
     * @return array
     * @phpstan-return array<string, array{0: string, 1: string[]}>
     */
    public function commandLineProvider()
    {
        // $method, $expected
        return [
            'original' => ['useOriginal', []],
            'standard' => ['useStandard', ['-n', '-c', '']],
            'persistent' => ['usePersistent', []],
        ];
    }

    /**
     * Tests that the environment is set correctly for each mode.
     *
     * @param string $iniFunc IniHelper method to use
     * @param false|string $scanDir Initial value for PHP_INI_SCAN_DIR
     * @param false|string $phprc Initial value for PHPRC
     * @dataProvider environmentProvider
     *
     * @return void
     */
    public function testEnvironment($iniFunc, $scanDir, $phprc)
    {
        $ini = EnvHelper::setInis($iniFunc, $scanDir, $phprc);

        $loaded = true;
        CoreMock::createAndCheck($loaded);
        $settings = CoreMock::getRestartSettings();

        if (null === $settings) {
            self::fail('getRestartSettings returned null');
        }

        $config = new PhpConfig();
        $tests = ['useOriginal', 'usePersistent', 'useStandard'];

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

    /**
     * @return array
     * @phpstan-return envTestData
     */
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
        $tests = ['PHP_INI_SCAN_DIR' => $scanDir, 'PHPRC' => $phprc];

        foreach ($tests as $env => $value) {
            $message = $name.' '.strtolower($env);
            self::assertSame($value, getenv($env), 'getenv '.$message);

            if (false === $value) {
                self::assertArrayNotHasKey($env, $_SERVER, '$_SERVER '.$message);
            } else {
                self::assertSame($value, $_SERVER[$env], '$_SERVER '.$message);
            }
        }
    }
}
