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
     * @param mixed $scanDir Initial value for PHP_INI_SCAN_DIR
     * @param $phprc Initial value for PHPRC
     * @package $standard If this is a standard restart
     * @dataProvider environmentProvider
     */
    public function testEnvironmentBeforeRestart($iniFunc, $scanDir, $phprc, $standard)
    {
        $ini = EnvHelper::setInis($iniFunc, $scanDir, $phprc);
        $loaded = true;

        // This needs to be implemented
        $settings = $standard ? array() : array();

        PartialMock::createAndCheck($loaded, null, $settings);

        if (!$standard) {
            //$scanDir = $ini->hasScannedInis() ? '' : $scanDir;
            //$phprc = $xdebug->getTmpIni();
        }

        $strategy = $standard ? 'standard' : 'persistent';
        $this->assertSame($scanDir, getenv('PHP_INI_SCAN_DIR'), $strategy.' scanDir');
        $this->assertSame($phprc, getenv('PHPRC'), $strategy.' phprc');
    }

    public function environmentProvider()
    {
        return EnvHelper::dataProviderEx();
    }
}
