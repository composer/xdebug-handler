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

use Composer\XdebugHandler\Mocks\CoreMock;

/**
 * This helper class allows us to mock the php ini files that the process would
 * otherwise report via php_ini_loaded_file and php_ini_scanned_files.
 */
class IniHelper
{
    protected $loadedIni;
    protected $scanDir;
    protected $files;
    protected $envOptions;

    /**
     * envOptions is an array of additional environment values to set,
     * comprising: [PHP_INI_SCAN_DIR, optional PHPRC]
     *
     * @param mixed $envOptions
     */
    public function __construct($envOptions = null)
    {
        $this->envOptions = $envOptions ?: array();
        $base = dirname(__DIR__).DIRECTORY_SEPARATOR.'Fixtures';
        $this->loadedIni = $base.DIRECTORY_SEPARATOR.'php.ini';
        $this->scanDir = $base.DIRECTORY_SEPARATOR.'scandir';
    }

    public function setNoInis()
    {
        // Must have at least one entry
        $this->files = array('');
        $this->setEnvironment();
    }

    public function setLoadedIni()
    {
        $this->files = array(
            $this->loadedIni,
        );

        $this->setEnvironment();
    }

    public function setScannedInis()
    {
        $this->files = array(
            '',
            $this->scanDir.DIRECTORY_SEPARATOR.'scan-one.ini',
            $this->scanDir.DIRECTORY_SEPARATOR.'scan-two.ini',
            $this->scanDir.DIRECTORY_SEPARATOR.'scan-empty.ini',
        );

        $this->setEnvironment();
    }

    public function setAllInis()
    {
        $this->files = array(
            $this->loadedIni,
            $this->scanDir.DIRECTORY_SEPARATOR.'scan-one.ini',
            $this->scanDir.DIRECTORY_SEPARATOR.'scan-two.ini',
            $this->scanDir.DIRECTORY_SEPARATOR.'scan-empty.ini',
        );

        $this->setEnvironment();
    }

    public function setInaccessibleIni()
    {
        $this->files = array(
            '',
            $this->scanDir.DIRECTORY_SEPARATOR.'scan-one.ini',
            $this->scanDir.DIRECTORY_SEPARATOR.'scan-two.ini',
            $this->scanDir.DIRECTORY_SEPARATOR.'scan-missing.ini',
        );

        $this->setEnvironment();
    }

    public function getIniFiles()
    {
        return $this->files;
    }

    public function hasScannedInis()
    {
        return count($this->files) > 1;
    }

    public function getLoadedIni()
    {
        return $this->loadedIni;
    }

    public function getScanDir()
    {
        return $this->scanDir;
    }

    private function setEnvironment()
    {
        // Set ORIGINAL_INIS. Values must be path-separated
        $this->setEnv(CoreMock::ORIGINAL_INIS, implode(PATH_SEPARATOR, $this->files));

        $options = $this->envOptions ?: array();

        if ($options) {
            $scanDir = array_shift($options);
            $phprc = array_shift($options);

            $this->setEnv('PHP_INI_SCAN_DIR', $scanDir);

            if (null !== $phprc) {
                $this->setEnv('PHPRC', $phprc);
            }
        }
    }

    private function setEnv($name, $value)
    {
        if (false !== $value) {
            putenv($name.'='.$value);
            $_SERVER[$name] = $value;
        } else {
            putenv($name);
            unset($_SERVER[$name]);
        }
    }
}
