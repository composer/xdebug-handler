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
    public const ENV_OUTPUT_INDENT = 'XDEBUG_HANDLER_TEST_INDENT';

    /** @var bool */
    private $isDisplay;

    /** @var int */
    private $indent = 0;

    public function __construct(bool $display)
    {
        $this->isDisplay = $display;

        if (!defined('STDOUT')) {
            define('STDOUT', fopen('php://stdout', 'w'));
        }

        if (false === ($indent = getenv(self::ENV_OUTPUT_INDENT))) {
            Process::setEnv(self::ENV_OUTPUT_INDENT, '0');
        } else {
            $this->indentIncrease();
        }
    }

    public function __destruct()
    {
        $this->indentDecrease();
    }

    public function write(string $message, string $name): void
    {
        $prefix = sprintf('%s[%d]', $name, getmypid());
        $color = $this->isDisplay && $name !== Logs::LOGGER_NAME;
        $text = $this->format($prefix, $message, $color);

        fwrite(STDOUT, $text);
        fflush(STDOUT);
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

    private function indentDecrease(): void
    {
        if (false !== ($indent = getenv(self::ENV_OUTPUT_INDENT))) {
            $this->indent = intval($indent) - 2;
            Process::setEnv(self::ENV_OUTPUT_INDENT, (string) $this->indent);
        }
    }

    private function indentIncrease(): void
    {
        if (false !== ($indent = getenv(self::ENV_OUTPUT_INDENT))) {
            $this->indent = intval($indent) + 2;
            Process::setEnv(self::ENV_OUTPUT_INDENT, (string) $this->indent);
        }
    }
}
