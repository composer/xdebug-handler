<?php

/*
 * This file is part of composer/xdebug-handler.
 *
 * (c) Composer <https://github.com/composer>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Composer\XdebugHandler\Tests\Helpers;

use Composer\XdebugHandler\Tests\Helpers\BaseTestCase;

/**
 * This helper class provides a central data provider that uses IniHelper to
 * mock environment settings.
 *
 * @phpstan-type envTestData array<string, array{0: string, 1: false|string, 2: false|string}>
 */
class EnvHelper
{
    /**
     * Mock the environment
     *
     * @param string $iniFunc IniHelper method to use
     * @param false|string $scanDir Initial value for PHP_INI_SCAN_DIR
     * @param false|string $phprc Initial value for PHPRC
     *
     * @return IniHelper
     */
    public static function setInis($iniFunc, $scanDir, $phprc)
    {
        $ini = new IniHelper(array($scanDir, $phprc));
        BaseTestCase::safeCall($ini, $iniFunc);

        return $ini;
    }

    /**
     * @return array
     * @phpstan-return envTestData
     */
    public static function dataProvider()
    {
        $ini = new IniHelper();
        $loaded = $ini->getLoadedIni();
        $scanDir = $ini->getScanDir();

        // $iniFunc, $scanDir, $phprc
        return array(
            'loaded false myini' => array('setLoadedIni', false, '/my.ini'),
            'loaded empty false' => array('setLoadedIni', '', false),
            'scanned false file' => array('setScannedInis', false, $loaded),
            'scanned dir false' => array('setScannedInis', $scanDir, false),
        );
    }
}
