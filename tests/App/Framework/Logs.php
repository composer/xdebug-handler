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

class Logs
{
    public const LOGGER_NAME = 'logger';

    /** @var array<list<LogItem>> */
    private $items = [];

    /**
     *
     * @param list<string> $outputLines
     */
    public function __construct(array $outputLines)
    {
        $pids = [];

        foreach ($outputLines as $line) {
            $line = trim($line);

            if (!(bool) preg_match('/^(.+)\\[(\\d+)\\](.+)$/', $line, $matches)) {
                continue;
            }

            $pid = (int) $matches[2];
            $item = new LogItem($pid, $matches[1], $matches[3]);

            if (!isset($pids[$pid])) {
                $this->items[] = [];
                $pids[$pid] = count($this->items) - 1;
            }

            $index = $pids[$pid];
            $this->items[$index][] = $item;
        }
    }

    /**
     *
     * @return list<LogItem>
     */
    public function getOutputForProcess(int $seqNo): array
    {
        $index = $seqNo - 1;

        if (isset($this->items[$index])) {
           return $this->items[$index];
        }

        return [];
    }

    /**
     *
     * @return list<LogItem>
     */
    public function getValuesForProcess(int $seqNo, bool $forLogger = true): array
    {
        $items = $this->getOutputForProcess($seqNo);

        if ($forLogger) {
            $result = $this->filterValuesForLogger($items);
        } else {
            $result = $this->filterValuesForScript($items);
        }

        return $result;
    }

    /**
     *
     * @return list<LogItem>
     */
    public function getValuesForProcessName(int $seqNo, string $name): array
    {
        $items = $this->getOutputForProcess($seqNo);

        return $this->filterValuesForScriptName($items, $name);
    }

    /**
     *
     * @param list<LogItem> $items
     */
    public function getItemFromList(array $items, int $seqNo, bool $complete = false): string
    {
        $index = $seqNo - 1;

        if (!isset($items[$index])) {
            throw new \LogicException('Log item not found at index: '.$index);
        }

        $item = $items[$index];

        if (!$complete) {
            return $item->value;
        }

        return sprintf('%s[%d] %s', $item->name, $item->pid, $item->value);
    }

    /**
     *
     * @param list<LogItem> $items
     * @return list<LogItem>
     */
    private function filterValuesForScript(array $items): array
    {
        $result =  array_filter($items, function($item) {
            return $item->name !== self::LOGGER_NAME;
        });

        return array_values($result);
    }

    /**
     *
     * @param list<LogItem> $items
     * @return list<LogItem>
     */
    private function filterValuesForScriptName(array $items, string $name): array
    {
        $result =  array_filter($items, function($item) use ($name) {
            return $item->name === $name;
        });

        return array_values($result);
    }

    /**
     *
     * @param list<LogItem> $items
     * @return list<LogItem>
     */
    private function filterValuesForLogger(array $items): array
    {
        $result = array_filter($items, function($item) {
            return $item->name === 'logger';
        });

        return array_values($result);
    }
}
