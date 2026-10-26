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

/**
 * This class provides the log output from processes run by the test framework.
 *
 * The following (truncated) output lines:
 *
 *     app-basic[413] start
 *     logger[413] Checking APP_BASIC_ALLOW_XDEBUG
 *     ...
 *     app-basic[414] start
 *     logger[414] Checking APP_BASIC_ALLOW_XDEBUG
 *     ...
 *     logger[413] Restarted process exited 0
 *
 * are stored in the items array as:
 *
 *     items[0] = [
 *       { pid: 413, name: app-basic, value: start },
 *       { pid: 413, name: logger, value: Checking APP_BASIC_ALLOW_XDEBUG },
 *       { pid: 413, name: logger, value: Restarted process exited 0 },
 *     ]
 *
 *     items[1] = [
 *       { pid: 414, name: app-basic, value: start },
 *       { pid: 414, name: logger, value: Checking APP_BASIC_ALLOW_XDEBUG },
 *     ]
 *
 * The zero-based indexes represent the process order. They are accessed by using
 * a one-based sequence number in a call to getDefault() or getNamed().
 *
 *   getDefault(1): returns logger values from process 0 (pid 413)
 *   getNamed(2, 'app-basic'): returns app-basic values from process 1 (pid 414)
 */
class Logs
{
    public const LOGGER_NAME = 'logger';

    /** @var array<list<LogItem>> */
    private $items = [];

    /**
     * @param list<string> $outputLines
     */
    public function __construct(array $outputLines)
    {
        $indexes = [];

        foreach ($outputLines as $line) {
            $line = trim($line);

            if (!Preg::isMatch('/^(.+)\[(\d+)\]\s(.+)$/', $line, $matches)) {
                continue;
            }

            $pid = (int) $matches[2];
            $item = new LogItem($pid, (string) $matches[1], (string) $matches[3]);

            if (!isset($indexes[$pid])) {
                $this->items[] = [];
                $indexes[$pid] = count($this->items) - 1;
            }

            $index = $indexes[$pid];
            $this->items[$index][] = $item;
        }
    }

    /**
     * Provides the debug output from a process
     *
     * This is the default xdebug-handler debug output.
     *
     * @param int $sequence The one-based sequence number of the process
     * @return ProcessLog
     */
    public function getDefault(int $sequence): ProcessLog
    {
        return $this->getProcessLog($sequence, 'logger');
    }

    /**
     * Provides the log output from a named script in a process
     *
     * This is the output from the script.
     *
     * @param int $sequence The one-based sequence number of the process
     * @param string $name The app name to filter on
     * @return ProcessLog
     */
    public function getNamed(int $sequence, string $name): ProcessLog
    {
        return $this->getProcessLog($sequence, $name);
    }

    /**
     * Provides either the default or named log output from a process
     *
     * @param int $sequence The one-based sequence number of the process
     * @param string $name The app name to filter on
     * @return ProcessLog
     */
    private function getProcessLog(int $sequence, string $name): ProcessLog
    {
        $items = $this->items[$sequence - 1] ?? [];

        $result =  array_filter($items, function($item) use ($name) {
            return $item->name === $name;
        });

        return new ProcessLog(array_values($result), $sequence);
    }
}
