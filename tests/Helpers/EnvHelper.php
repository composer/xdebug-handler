<?php

/*
 * This file is part of composer/xdebug-handler.
 *
 * (c) Composer <https://github.com/composer>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Composer\XdebugHandler\Helpers;

/**
 * This helper class provides a central data provider that uses IniHelper to
 * mock environment settings.
 */
class EnvHelper
{
    /**
     * Mock the environment
     *
     * @param callable $iniFunc IniHelper method to use
     * @param mixed $scanDir Initial value for PHP_INI_SCAN_DIR
     * @param mixed $phprc Initial value for PHPRC
     *
     * @return IniHelper
     */
    public static function setInis($iniFunc, $scanDir, $phprc)
    {
        $ini = new IniHelper(array($scanDir, $phprc));
        call_user_func(array($ini, $iniFunc));

        return $ini;
    }

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
