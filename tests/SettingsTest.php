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

use Composer\XdebugHandler\Tests\Helpers\BaseTestCase;
use Composer\XdebugHandler\Tests\Helpers\EnvHelper;
use Composer\XdebugHandler\Tests\Mocks\CoreMock;

/**
 * We use PHP_BINARY which only became available in PHP 5.4
 *
 * @requires PHP 5.4
 */
class SettingsTest extends BaseTestCase
{
    /**
     * Tests that the restart settings are correctly set.
     *
     * @param string $iniFunc IniHelper method to use
     * @param false|string $scanDir Initial value for PHP_INI_SCAN_DIR
     * @param false|string $phprc Initial value for PHPRC
     * @dataProvider environmentProvider
     */
    public function testGetRestartSettings($iniFunc, $scanDir, $phprc)
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

        $this->assertTrue(is_string($settings['tmpIni']));
        $this->assertSame($ini->hasScannedInis(), $settings['scannedInis']);
        $this->assertSame($scanDir, $settings['scanDir']);
        $this->assertSame($phprc, $settings['phprc']);
        $this->assertSame(CoreMock::getAllIniFiles(), $settings['inis']);
        $this->assertSame(CoreMock::TEST_VERSION, $settings['skipped']);
    }

    public function environmentProvider()
    {
        return EnvHelper::dataProvider();
    }

    /**
     * Tests that a call with existing restart settings updates the current
     * settings.
     */
    public function testSyncSettings()
    {
        $ini = EnvHelper::setInis('setAllInis', false, false);

        // Create the settings in the environment
        $loaded = true;
        CoreMock::createAndCheck($loaded);
        $originalInis = getenv(CoreMock::ORIGINAL_INIS);

        // Unset env ORIGINAL_INIS to mock a call by a different application
        putenv(CoreMock::ORIGINAL_INIS);
        unset($_SERVER[CoreMock::ORIGINAL_INIS]);

        // Mock not loaded ($inRestart and $skipped statics are unset)
        $loaded = false;
        CoreMock::createAndCheck($loaded);

        // Env ORIGINAL_INIS must be set and be a string
        $this->assertSame($originalInis, getenv(CoreMock::ORIGINAL_INIS));
        $this->assertSame($originalInis, $_SERVER[CoreMock::ORIGINAL_INIS]);

        // Skipped version must be set
        $this->assertSame(CoreMock::TEST_VERSION, CoreMock::getSkippedVersion());
    }
}
