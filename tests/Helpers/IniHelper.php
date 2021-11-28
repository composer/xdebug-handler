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

use Composer\XdebugHandler\Tests\Mocks\CoreMock;

/**
 * This helper class allows us to mock the php ini files that the process would
 * otherwise report via php_ini_loaded_file and php_ini_scanned_files.
 */
class IniHelper
{
    /** @var string */
    protected $loadedIni;

    /** @var string */
    protected $scanDir;

    /** @var string[] */
    protected $files;

    /**
     * @var null|array
     * @phpstan-var null|array{0?: false|string, 1?: false|string}
     */
    protected $envOptions;

    /**
     * envOptions is an array of additional environment values to set,
     * comprising: [PHP_INI_SCAN_DIR, optional PHPRC]
     *
     * @param null|array $envOptions
     * @phpstan-param null|array{0?: false|string, 1?: false|string} $envOptions
     */
    public function __construct($envOptions = null)
    {
        $this->envOptions = $envOptions ?: array();
        $base = dirname(__DIR__).DIRECTORY_SEPARATOR.'Fixtures';
        $this->loadedIni = $base.DIRECTORY_SEPARATOR.'php.ini';
        $this->scanDir = $base.DIRECTORY_SEPARATOR.'scandir';
    }

    /**
     * @return void
     */
    public function setNoInis()
    {
        // Must have at least one entry
        $this->files = array('');
        $this->setEnvironment();
    }

    /**
     * @return void
     */
    public function setLoadedIni()
    {
        $this->files = array(
            $this->loadedIni,
        );

        $this->setEnvironment();
    }

    /**
     * @return void
     */
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

    /**
     * @return void
     */
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

    /**
     * @return void
     */
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

    /**
     * @param string $sectionName
     * @return void
     */
    public function setSectionInis($sectionName)
    {
        $this->files = array(
            $this->loadedIni,
            $this->scanDir.DIRECTORY_SEPARATOR.'section-first.ini',
            $this->scanDir.DIRECTORY_SEPARATOR.'section-'.$sectionName.'.ini',
        );

        $this->setEnvironment();
    }

    /**
     * @return string[]
     */
    public function getIniFiles()
    {
        return $this->files;
    }

    /**
     * @return bool
     */
    public function hasScannedInis()
    {
        return count($this->files) > 1;
    }

    /**
     * @return string
     */
    public function getLoadedIni()
    {
        return $this->loadedIni;
    }

    /**
     * @return string
     */
    public function getScanDir()
    {
        return $this->scanDir;
    }

    /**
     * @return void
     */
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

    /**
     * @param string $name
     * @param string|false $value
     *
     * @return void
     */
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
