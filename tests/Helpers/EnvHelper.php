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

    /**
     * Checks if php_ini_scanned_files is supported.
     *
     * The process will not be restarted if there are scanned inis but PHP
     * is unable to list them. See https://bugs.php.net/73124
     *
     * This method tests the behaviour of XdebugHandler::checkScanDirConfig by
     * checking each requirement separately.
     *
     * @param mixed $scanDir Initial value for PHP_INI_SCAN_DIR
     *
     * @return null|string The skip message
     */
    public static function shouldSkipTest($scanDir)
    {
        // Not relevant if no scan dir or it has been overriden
        if ($scanDir === false || $scanDir === '') {
            return;
        }

        // Not relevant if --with-config-file-scan-dir was used
        if (PHP_CONFIG_FILE_SCAN_DIR !== '') {
            return;
        }

        // Bug fixed in 7.1.13
        if (PHP_VERSION_ID >= 70113 && PHP_VERSION_ID < 70200) {
            return;
        }

        // Bug fixed in 7.2.1
        if (PHP_VERSION_ID >= 70201) {
            return;
        }

        return 'php_ini_scanned_files not functional on '.PHP_VERSION;
    }
}
