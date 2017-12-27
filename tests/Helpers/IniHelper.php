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
    protected $base;
    protected $files;

    public function __construct()
    {
        $this->base = dirname(__DIR__).DIRECTORY_SEPARATOR.'Fixtures';
    }

    public function setNoInis()
    {
        // Must have at least one entry
        $this->files = array('');
        $this->setEnvIni();
    }

    public function setLoadedIni()
    {
        $this->files = array(
            $this->base.DIRECTORY_SEPARATOR.'php.ini',
        );

        $this->setEnvIni();
    }

    public function setScannedInis()
    {
        $this->files = array(
            '',
            $this->base.DIRECTORY_SEPARATOR.'scan-one.ini',
            $this->base.DIRECTORY_SEPARATOR.'scan-two.ini',
        );

        $this->setEnvIni();
    }

    public function setAllInis()
    {
        $this->files = array(
            $this->base.DIRECTORY_SEPARATOR.'php.ini',
            $this->base.DIRECTORY_SEPARATOR.'scan-one.ini',
            $this->base.DIRECTORY_SEPARATOR.'scan-two.ini',
        );

        $this->setEnvIni();
    }

    public function getIniFiles()
    {
        return $this->files;
    }

    private function setEnvIni()
    {
        // Values must be path-separated
        putenv(CoreMock::ORIGINAL_INIS.'='.implode(PATH_SEPARATOR, $this->files));
    }
}
