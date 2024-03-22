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

use Composer\XdebugHandler\Process;

class PhpExecutor
{
    public function run(string $script, PhpOptions $options, ?string $cwd = null): string
    {
        $content = null;
        $params = array_merge([PHP_BINARY], $options->getPhpArgs());

        if ($options->getStdin()) {
            $content = $this->getContents($script);
        } else {
            $params[] = $script;
        }

        $params = array_merge($params, $options->getScriptArgs());

        $this->setEnvs($options->getEnvs());

        try {
            $output = $this->execute($params, $cwd, $content, $options->getPassthru());
        } finally {
            $this->unsetEnvs($options->getEnvs());
        }

        return $output;
    }

    /**
     *
     * @param non-empty-list<string> $args
     */
    private function execute(array $args, ?string $cwd, ?string $stdin, bool $passthru): string
    {
        $command = $this->getCommand($args);
        $output = '';
        $fds = [];

        if ($stdin !== null) {
            $fds[0] = ['pipe', 'r'];
        }

        if (!$passthru) {
            $fds[1] = ['pipe', 'wb'];
        }

        $process = proc_open($command, $fds, $pipes, $cwd);

        if (is_resource($process)) {
            if ($stdin !== null) {
                fwrite($pipes[0], $stdin);
                fclose($pipes[0]);
            }

            if (!$passthru) {
                $output = stream_get_contents($pipes[1]);
                fclose($pipes[1]);
            }

            $exitCode = proc_close($process);
        }

        return (string) $output;
    }

    /**
     *
     * @param non-empty-list<string> $args
     * @return non-empty-list<string>|string
     */
    private function getCommand(array $args)
    {
        if (PHP_VERSION_ID >= 70400) {
            return $args;
        }

        $command = Process::escapeShellCommand($args);

        if (defined('PHP_WINDOWS_VERSION_BUILD')) {
            // Outer quotes required on cmd string below PHP 8
            $command = '"'.$command.'"';
        }

        return $command;
    }

    private function getContents(string $file): string
    {
        $result = file_get_contents($file);

        if ($result !== false) {
            return $result;
        }

        throw new \RuntimeException('Unable to read file: '.$file);
    }

    /**
     *
     * @param array<string, string> $envs
     */
    private function setEnvs(array $envs): void
    {
        foreach ($envs as $name => $value) {
            Process::setEnv($name, $value);
        }
    }

    /**
     *
     * @param array<string, string> $envs
     */
    private function unsetEnvs(array $envs): void
    {
        foreach ($envs as $name => $value) {
            Process::setEnv($name);
        }
    }
}
