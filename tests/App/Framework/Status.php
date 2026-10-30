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
 * This class provides xdebug-handler settings and values.
 *
 * @phpstan-import-type restartData from \Composer\XdebugHandler\PhpConfig
 */
class Status
{
    private const AVAILABLE = 'available';
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
     * Returns a list of xdebug-handler settings and values
     *
     * @param class-string $className
     * @return list<string>
     */
    public function getWorkingsStatus(string $className): array
    {
        $this->loaded = extension_loaded('xdebug');
        $result = [];

        // status
        $status = $this->getXdebugStatus();
        $result[] = sprintf('[xdebug] %s', $status);

        /** @var restartData|null $settings */
        $settings = $className::getRestartSettings();

        if ($settings === null) {
            $shortSettings = $this->loaded ? self::INVALID : self::MISSING;
        } else {
            $shortSettings = self::AVAILABLE;
        }

        if (!$this->isDisplay) {
            $result[] = sprintf('[settings] %s', $shortSettings);
            return $result;
        }

        // active
        $active = $this->getActive($className);
        $result[] = sprintf('[active] %s', $active);

        // skipped
        $skipped = $this->getSkipped($className);
        $result[] = sprintf('[skipped] %s', $skipped);

        // envs
        foreach ($this->getEnvs() as $item) {
            $result[] = sprintf('[env] %s', $item);
        }

        // inis
        foreach ($this->getInis($className) as $item) {
            $result[] = sprintf('[ini] %s', $item);
        }

        // settings
        foreach ($this->getSettings($settings) as $item) {
            $result[] = sprintf('[settings] %s', $item);
        }

        return $result;
    }

    private function getXdebugStatus(): string
    {
        $suffix = $this->loaded ? 'loaded' : 'NOT loaded';

        return sprintf('extension %s', $suffix);
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
        /** @var string $version */
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
        /** @var non-empty-list<string> $iniFiles */
        $iniFiles = $className::getAllIniFiles();
        if ($iniFiles[0] === '') {
            $iniFiles[0] = '(none)';
        }

        $count = count($iniFiles);
        if ($this->showInis || $count === 1) {
            return $iniFiles;
        }

        $message = sprintf("%d more ini files (show with '--inis' option)", $count - 1);

        return [$iniFiles[0], $message];
    }

    /**
     *
     * @param restartData|null $settings
     * @return list<string>
     */
    private function getSettings(?array $settings): array
    {
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
