<?php

/*
 * This file is part of composer/xdebug-handler.
 *
 * (c) Composer <https://github.com/composer>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Composer\XdebugHandler\Tests;

use Composer\XdebugHandler\Tests\Helpers\BaseTestCase;
use Composer\XdebugHandler\Tests\Helpers\EnvHelper;
use Composer\XdebugHandler\Tests\Mocks\CoreMock;

/**
 * @phpstan-import-type envTestData from EnvHelper
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
    public function testGetRestartSettings(string $iniFunc, $scanDir, $phprc): void
    {
        $ini = EnvHelper::setInis($iniFunc, $scanDir, $phprc);

        $loaded = true;
        CoreMock::createAndCheck($loaded);

        $settings = CoreMock::getRestartSettings();

        if (null === $settings) {
            self::fail('getRestartSettings returned null');
        }

        self::assertNotEmpty($settings['tmpIni']);
        self::assertSame($ini->hasScannedInis(), $settings['scannedInis']);
        self::assertSame($scanDir, $settings['scanDir']);
        self::assertSame($phprc, $settings['phprc']);
        self::assertSame(CoreMock::getAllIniFiles(), $settings['inis']);
        self::assertSame(CoreMock::TEST_VERSION, $settings['skipped']);
    }

    /**
     * @phpstan-return envTestData
     */
    public static function environmentProvider(): array
    {
        return EnvHelper::dataProvider();
    }

    /**
     * Tests that a call with existing restart settings updates the current
     * settings
     */
    public function testSyncSettings(): void
    {
        $ini = EnvHelper::setInis('setAllInis', false, false);

        // Create the settings in the environment
        $loaded = true;
        CoreMock::createAndCheck($loaded);
        $originalInis = getenv(CoreMock::ORIGINAL_INIS);

        // Unset env ORIGINAL_INIS to mock a call by a different application
        putenv(CoreMock::ORIGINAL_INIS);
        unset($_SERVER[CoreMock::ORIGINAL_INIS]);

        // Mock not loaded (static $skipped is unset in mock constructor)
        $loaded = false;
        CoreMock::createAndCheck($loaded);

        // Env ORIGINAL_INIS must be set and be a string
        self::assertSame($originalInis, getenv(CoreMock::ORIGINAL_INIS));
        self::assertSame($originalInis, $_SERVER[CoreMock::ORIGINAL_INIS]);

        // Skipped version must be set
        self::assertSame(CoreMock::TEST_VERSION, CoreMock::getSkippedVersion());
    }
}
