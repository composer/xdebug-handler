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
     * @phpstan-var null|array{0: false|string, 1: false|string}
     */
    protected $envOptions;

    /**
     * envOptions is an array of additional environment values to set,
     * comprising: [PHP_INI_SCAN_DIR, optional PHPRC]
     *
     * @phpstan-param null|array{0: false|string, 1: false|string} $envOptions
     */
    public function __construct(?array $envOptions = null)
    {
        $this->envOptions = $envOptions;
        $base = dirname(__DIR__).DIRECTORY_SEPARATOR.'Fixtures';
        $this->loadedIni = $base.DIRECTORY_SEPARATOR.'php.ini';
        $this->scanDir = $base.DIRECTORY_SEPARATOR.'scandir';
    }

    public function setNoInis(): void
    {
        // Must have at least one entry
        $this->files = [''];
        $this->setEnvironment();
    }

    public function setLoadedIni(): void
    {
        $this->files = [
            $this->loadedIni,
        ];

        $this->setEnvironment();
    }

    public function setScannedInis(): void
    {
        $this->files = [
            '',
            $this->scanDir.DIRECTORY_SEPARATOR.'scan-one.ini',
            $this->scanDir.DIRECTORY_SEPARATOR.'scan-two.ini',
            $this->scanDir.DIRECTORY_SEPARATOR.'scan-empty.ini',
        ];

        $this->setEnvironment();
    }

    public function setAllInis(): void
    {
        $this->files = [
            $this->loadedIni,
            $this->scanDir.DIRECTORY_SEPARATOR.'scan-one.ini',
            $this->scanDir.DIRECTORY_SEPARATOR.'scan-two.ini',
            $this->scanDir.DIRECTORY_SEPARATOR.'scan-empty.ini',
        ];

        $this->setEnvironment();
    }

    public function setInaccessibleIni(): void
    {
        $this->files = [
            '',
            $this->scanDir.DIRECTORY_SEPARATOR.'scan-one.ini',
            $this->scanDir.DIRECTORY_SEPARATOR.'scan-two.ini',
            $this->scanDir.DIRECTORY_SEPARATOR.'scan-missing.ini',
        ];

        $this->setEnvironment();
    }

    public function setSectionInis(string $sectionName): void
    {
        $this->files = [
            $this->loadedIni,
            $this->scanDir.DIRECTORY_SEPARATOR.'section-first.ini',
            $this->scanDir.DIRECTORY_SEPARATOR.'section-'.$sectionName.'.ini',
        ];

        $this->setEnvironment();
    }

    /**
     * @return string[]
     */
    public function getIniFiles(): array
    {
        return $this->files;
    }

    public function hasScannedInis(): bool
    {
        return count($this->files) > 1;
    }

    public function getLoadedIni(): string
    {
        return $this->loadedIni;
    }

    public function getScanDir(): string
    {
        return $this->scanDir;
    }

    private function setEnvironment(): void
    {
        // Set ORIGINAL_INIS. Values must be path-separated
        $this->setEnv(CoreMock::ORIGINAL_INIS, implode(PATH_SEPARATOR, $this->files));

        if ($this->envOptions !== null) {
            list($scanDir, $phprc) = $this->envOptions;
            $this->setEnv('PHP_INI_SCAN_DIR', $scanDir);
            $this->setEnv('PHPRC', $phprc);
        }
    }

    /**
     * @param string $name
     * @param string|false $value
     */
    private function setEnv(string $name, $value): void
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
