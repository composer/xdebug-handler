<?php

declare(strict_types=1);

/*
 * This file is part of composer/xdebug-handler.
 *
 * (c) Composer <https://github.com/composer>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Composer\XdebugHandler\Tests\App\Framework;

use Composer\XdebugHandler\XdebugHandler;

/**
 * @phpstan-import-type restartData from \Composer\XdebugHandler\PhpConfig
 */
class Status
{
    private const INVALID = 'not applicable';
    private const MISSING = 'not available';

    /** @var bool */
    private $isDisplay;
    /** @var bool */
    private $showInis;
    /** @var bool */
    private $loaded = false;

    public function __construct(bool $display, bool $showInis)
    {
        $this->isDisplay = $display;
        $this->showInis = $showInis;
    }

    /**
     *
     * @param class-string $className
     * @return list<string>
     */
    public function getWorkingsStatus(string $className): array
    {
        $this->loaded = extension_loaded('xdebug');
        $result = [];

        // status
        [$status, $info] = $this->getXdebugStatus();
        $result[] = sprintf('[%s] %s', $status, $info);

        if (!$this->isDisplay) {
            return $result;
        }

        // active
        $active = $this->getActive($className);
        $result[] = sprintf('[active] %s', $active);

        // skipped
        $skipped = $this->getSkipped($className);
        $result[] = sprintf('[skipped] %s', $skipped);

        // envs
        foreach($this->getEnvs() as $item) {
            $result[] = sprintf('[env] %s', $item);
        }

        // inis
        foreach($this->getInis($className) as $item) {
            $result[] = sprintf('[ini] %s', $item);
        }

        // settings
        foreach($this->getSettings($className) as $item) {
            $result[] = sprintf('[settings] %s', $item);
        }

        return $result;
    }

    /**
     *
     * @return array{0: string, 1: string}
     */
    private function getXdebugStatus(): array
    {
        $status = $this->loaded ? 'XDEBUG' : 'NO XDEBUG';
        $text = 'The Xdebug extension is';
        $suffix = $this->loaded ? 'loaded' : 'not loaded';
        $info = sprintf('%s %s', $text, $suffix);

        return [$status, $info];
    }

    /**
     *
     * @param class-string $className
     */
    private function getActive(string $className): string
    {
        $active = $className::isXdebugActive();

        return $active === true ? 'true' : 'false';
    }

    /**
     *
     * @param class-string $className
     */
    private function getSkipped(string $className): string
    {
        $version = $className::getSkippedVersion();

        if ($version === '') {
            return $this->loaded ? self::INVALID : self::MISSING;
        }

        return $version;
    }

    /**
     * @return list<string>
     */
    private function getEnvs(): array
    {
        $result = [];
        $envs = ['PHPRC', 'PHP_INI_SCAN_DIR'];

        foreach ($envs as $name) {
            $format = '%s=%s';

            if (false === ($env = getenv($name))) {
                $env = 'unset';
                $format = '%s %s';
            } elseif ($env === '') {
                $env = "''";
            }

            $result[] = sprintf($format, $name, $env);
        }

        return $result;
    }

    /**
     * @param class-string $className
     * @return list<string>
     */
    private function getInis(string $className): array
    {
        $iniFiles = $this->findIniFiles($className);

        if ($iniFiles[0] === '') {
            array_shift($iniFiles);
        }

        $count = count($iniFiles);

        if ($this->showInis || $count === 1) {
            return $iniFiles;
        }

        $message = sprintf("%d more ini files (show with '--inis' option)", $count - 1);

        return [$iniFiles[0], $message];
    }

    /**
     * @param class-string $className
     * @return list<string>
     */
    private function findIniFiles(string $className): array
    {
        $extended = $className !== XdebugHandler::class;

        if ($extended) {
            $iniFiles = $className::getAllIniFiles();
        } elseif (class_exists(XdebugHandler::class)) {
            $iniFiles = $className::getAllIniFiles();
        } else {
            $iniFiles = array((string) php_ini_loaded_file());
            $scanned = php_ini_scanned_files();

            if ($scanned !== false) {
                $iniFiles = array_merge($iniFiles, array_map('trim', explode(',', $scanned)));
            }
        }

        // @phpstan-ignore-next-line
        return !is_array($iniFiles) ? [self::MISSING] : $iniFiles;
    }

    /**
     *
     * @param class-string $className
     * @return list<string>
     */
    private function getSettings(string $className): array
    {
        /** @var restartData|null $settings */
        $settings = $className::getRestartSettings();

        if ($settings === null) {
            return $this->loaded ? [self::INVALID] : [self::MISSING];
        }

        $settings['scannedInis'] = true === $settings['scannedInis'] ? 'true' : 'false';
        $settings['scanDir'] = false === $settings['scanDir'] ? 'false' : $settings['scanDir'];
        $settings['phprc'] = false === $settings['phprc'] ? 'false' : $settings['phprc'];
        $settings['skipped'] = '' === $settings['skipped'] ? "''" : $settings['skipped'];

        $iniCount = count($settings['inis']);
        if ($iniCount === 1) {
            $settings['inis'] = $settings['inis'][0];
        } else {
            $settings['inis'] = sprintf('(%d inis)', $iniCount);
        }

        $data = [];
        foreach($settings as $key => $value) {
            $data[] = sprintf('%s=%s', $key, $value);
        }
        return $data;
    }
}
