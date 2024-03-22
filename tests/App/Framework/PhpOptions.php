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

class PhpOptions
{
    /** @var list<string> */
    private $phpArgs = [];
    /** @var list<string> */
    private $scriptArgs = [];
    /** @var array<string, string> */
    private $envs = [];
    /** @var bool */
    private $stdin = false;
    /** @var bool */
    private $passthru = false;

    /**
     * @return list<string>
     */
    public function getPhpArgs(): array
    {
        return $this->phpArgs;
    }

    /**
     * @return list<string>
     */
    public function getScriptArgs(): array
    {
        return $this->scriptArgs;
    }

    /**
     * @return array<string, string>
     */
    public function getEnvs(): array
    {
        return $this->envs;
    }

    public function getStdin(): bool
    {
        return $this->stdin;
    }

    public function getPassthru(): bool
    {
        return $this->passthru;
    }

    /**
     *
     * @param string ...$phpArgs
     */
    public function addPhpArgs(...$phpArgs): void
    {
        foreach ($phpArgs as $item) {
            $this->phpArgs[] = $item;
        }
    }

    /**
     *
     * @param string ...$scriptArgs
     */
    public function addScriptArgs(...$scriptArgs): void
    {
        foreach ($scriptArgs as $item) {
            $this->scriptArgs[] = $item;
        }
    }

    public function addEnv(string $name, string $value): void
    {
        $this->envs[$name] = $value;
    }

    public function setPassthru(bool $value): void
    {
        $this->passthru = $value;
    }

    public function setStdin(bool $value): void
    {
        $this->stdin = $value;
    }
}
