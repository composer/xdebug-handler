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
use Composer\XdebugHandler\Helpers\EnvHelper;
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
     * @param mixed $scanDir Initial value for PHP_INI_SCAN_DIR
     * @param mixed $phprc Initial value for PHPRC
     *
     * @dataProvider envAllowBeforeProvider
     */
    public function testEnvAllowBeforeRestart($iniFunc, $scanDir, $phprc)
    {
        if ($message = EnvHelper::shouldSkipTest($scanDir)) {
            $this->markTestSkipped($message);
        }

        $ini = EnvHelper::setInis($iniFunc, $scanDir, $phprc);
        $loaded = true;

        PartialMock::createAndCheck($loaded);

        $args = array(
            PartialMock::RESTART_ID,
            PartialMock::TEST_VERSION,
            $ini->hasScannedInis() ? '1' : '0',
            false !== $scanDir ? $scanDir : '*',
            false !== $phprc ? $phprc : '*',
        );

        $expected = implode('|', $args);
        $this->assertSame($expected, getenv(PartialMock::ALLOW_XDEBUG));
    }

    public function envAllowBeforeProvider()
    {
        return EnvHelper::dataProvider();
    }

    /**
     * Tests that environment variables are correctly set for a restart.
     *
     * @param callable $iniFunc IniHelper method to use
     * @param false|string $scanDir Initial value for PHP_INI_SCAN_DIR
     * @param false|string $phprc Initial value for PHPRC
     * @param bool $standard If this is a standard restart
     *
     * @dataProvider environmentProvider
     */
    public function testEnvironmentBeforeRestart($iniFunc, $scanDir, $phprc, $standard)
    {
        if ($message = EnvHelper::shouldSkipTest($scanDir)) {
            $this->markTestSkipped($message);
        }

        $ini = EnvHelper::setInis($iniFunc, $scanDir, $phprc);
        $loaded = true;

        $settings = $standard ? array() : array('setPersistent' => array());

        $xdebug = PartialMock::createAndCheck($loaded, null, $settings);

        if (!$standard) {
            $scanDir = '';
            $phprc = $xdebug->getTmpIni();
        }

        $strategy = $standard ? 'standard' : 'persistent';
        $this->assertSame($scanDir, getenv('PHP_INI_SCAN_DIR'), $strategy.' scanDir');
        $this->assertSame($phprc, getenv('PHPRC'), $strategy.' phprc');
    }

    public function environmentProvider()
    {
        // $iniFunc, $scanDir, $phprc, $standard (added below)
        $data = EnvHelper::dataProvider();
        $result = array();

        foreach ($data as $test => $params) {
            $result[$test.' standard'] = array_merge($params, array(true));
            $result[$test.' persistent'] = array_merge($params, array(false));
        }

        return $result;
    }
}
