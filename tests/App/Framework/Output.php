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

use Composer\Pcre\Preg;
use Composer\XdebugHandler\Process;

class Output
{
    public const ENV_OUTPUT_PIDS = 'XDEBUG_HANDLER_TEST_PID';

    /** @var bool */
    private $isDisplay;

    /** @var int */
    private $indent;

    /** @var int */
    private $pid;

    public function __construct(bool $display)
    {
        $this->isDisplay = $display;
        $this->pid = (int) getmypid();
        $this->indent = $this->calculateIndent();
    }

    public function write(string $message, string $name): void
    {
        $prefix = sprintf('%s[%d]', $name, $this->pid);
        $color = $this->isDisplay && $name !== Logs::LOGGER_NAME;
        $text = $this->format($prefix, $message, $color);

        print($text);
    }

    private function calculateIndent(): int
    {
        $envPids = (string) getenv(self::ENV_OUTPUT_PIDS);

        if ('' === $envPids) {
            $pids = [];
        } else {
            $pids = array_map(function ($item) {
                return (int) $item;
            }, explode(',', $envPids));
        }

        if (!in_array($this->pid, $pids, true)) {
            $pids[] = $this->pid;
        } elseif ($pids[count($pids) - 1] !== $this->pid) {
            array_pop($pids);
        }

        Process::setEnv(self::ENV_OUTPUT_PIDS, implode(',', $pids));

        return (count($pids) - 1) * 2;
    }

    private function format(string $prefix, string $text, bool $color): string
    {
        if ($color) {
            $prefix = sprintf("\033[33;33m%s\033[0m", $prefix);

            if (Preg::isMatch('/(^working|^initial)/', $text, $matches)) {
                $info = substr($text, 7);
                $text = sprintf("\033[0;32m%s\033[0m", $matches[1]);
                $text .= sprintf("\033[0;93m%s\033[0m", $info);
            } else {
                $text = sprintf("\033[0;32m%s\033[0m", $text);
            }
        }

        $text = sprintf('%s %s%s', $prefix, $text, PHP_EOL);

        if ($this->indent > 0) {
            $prefix = str_repeat(chr(32), $this->indent);
            $text = $prefix.$text;
        }

        return $text;
    }
}
