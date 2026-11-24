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
    private $useStderr = false;
    /** @var bool */
    private $useStdin = false;
    /** @var bool */
    private $captureOutput = false;

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

    public function useStderr(?bool $value = null): bool
    {
        if ($value !== null) {
            $this->useStderr = $value;
        }
        return $this->useStderr;
    }

    public function useStdin(?bool $value = null): bool
    {
        if ($value !== null) {
            $this->useStdin = $value;
        }
        return $this->useStdin;
    }

    public function captureOutput(?bool $value = null): bool
    {
        if ($value !== null) {
            $this->captureOutput = $value;
        }
        return $this->captureOutput;
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
}
