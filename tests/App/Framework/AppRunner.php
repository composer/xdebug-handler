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

/**
 * This class runs and provides functionality for the xdebug-handler functional
 * tests.
 */
class AppRunner
{
    /** @var string */
    protected $scriptDir;
    /** @var PhpExecutor */
    protected $phpExecutor;

    public function __construct(?string $scriptDir = null)
    {
        $this->scriptDir = (string) realpath($scriptDir ?? __DIR__);

        if (!is_dir($this->scriptDir)) {
            throw new \RuntimeException('Directory does not exist: '.$this->scriptDir);
        }

        $this->phpExecutor = new PhpExecutor();
    }

    /**
     * Runs a script and returns its captured output
     */
    public function run(string $script, ?PhpOptions $options = null, bool $allow = false): Logs
    {
        $script = $this->checkScript($script);

        if ($options === null) {
            $options = new PhpOptions();
        }

        // enforce output
        $options->setPassthru(false);

        if ($allow) {
            $options->addEnv($this->getEnvAllow($script), '1');
        }

        $output = $this->phpExecutor->run($script, $options, $this->scriptDir);

        $lines = preg_split("/\r\n|\n\r|\r|\n/", trim($output));
        $outputLines = $lines !== false ? $lines : [];

        return new Logs($outputLines);
    }

    public function getAppName(string $script): string
    {
        return basename($script, '.php');
    }

    public function getEnvAllow(string $script): string
    {
        return sprintf('%s_ALLOW_XDEBUG', $this->getEnvPrefix($script));
    }

    public function getEnvPrefix(string $script): string
    {
        $name = $this->getAppName($script);

        return strtoupper(str_replace(array('-', ' '), '_', $name));
    }

    protected function checkScript(string $script): string
    {
        if (file_exists($script)) {
            return $script;
        }

        $path = $this->scriptDir.'/'.$script;

        if (file_exists($path)) {
            return $path;
        }

        throw new \RuntimeException('File does not exist: '.$script);
    }
}
