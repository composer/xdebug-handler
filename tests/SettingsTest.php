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

/**
 * We use PHP_BINARY which only became available in PHP 5.4
 *
 * @requires PHP 5.4
 */
class SettingsTest extends BaseTestCase
{
    /**
     * Tests that the settings returned from getRestartSettings are correctly
     * formatted.
     *
     * Other tests are performed in BaseTestCase checks, and the EnvironmentTest
     * method testScanDirAfterRestart.
     */
    public function testGetRestartSettings()
    {
        $settings = $this->getRestartSettings();

        $this->assertInternalType('string', $settings['tmpIni']);
        $this->assertInternalType('boolean', $settings['scannedInis']);
        // Note scanDir is checked specifically in Environment tests
        $this->assertArrayHasKey('scanDir', $settings);
        $this->assertInternalType('array', $settings['inis']);
        $this->assertInternalType('string', $settings['skipped']);
    }

    /**
     * Tests that a call with existing restart settings updates the current
     * settings.
     */
    public function testSyncSettings()
    {
        // Create the settings in the environment
        $this->getRestartSettings();

        // Unset env ORIGINAL_INIS to mock a call by a different application
        putenv(CoreMock::ORIGINAL_INIS);
        unset($_SERVER[CoreMock::ORIGINAL_INIS]);

        // Mock not loaded ($inRestart and $skipped statics are unset)
        $loaded = false;
        $xdebug = CoreMock::createAndCheck($loaded);

        // Env ORIGINAL_INIS must be set and be a string
        $this->assertInternalType('string', getenv(CoreMock::ORIGINAL_INIS));
        $this->assertSame(true, isset($_SERVER[CoreMock::ORIGINAL_INIS]));

        // Skipped version must be set
        $this->assertSame(CoreMock::TEST_VERSION, $xdebug::getSkippedVersion());
    }

    /**
     * Returns restart settings from a mocked restart.
     */
    private function getRestartSettings()
    {
        $loaded = true;
        $xdebug = CoreMock::createAndCheck($loaded);
        return CoreMock::getRestartSettings();
    }
}
