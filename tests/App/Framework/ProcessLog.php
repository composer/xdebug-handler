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

class ProcessLog
{
    /** @var list<LogItem> */
    private $items = [];

    /** @var string */
    private $log;

    /** @var int */
    private $sequence;

    /**
     * @param list<LogItem> $items
     */
    public function __construct(array $items, int $sequence)
    {
        $this->items = $items;
        $this->sequence = $sequence;

        $list =  array_map(function ($item) {
            /** @var LogItem $item */
            return sprintf('%s[%d] %s', $item->name, $item->pid, $item->value);
        }, $items);

        $this->log = implode("\n", $list);
    }

    public function getItem(int $index): LogItem
    {
        if (!isset($this->items[$index])) {
            throw new \LogicException('Log item not found at index: '.$index);
        }

        return $this->items[$index];
    }

    /**
     * @return list<LogItem>
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * Returns a string representation of the log output for this process
     *
     * @param string|null $prefix
     * @return string
     */
    public function getOutputInfo(?string $prefix = null): string
    {
        $parts = $prefix !== null ? [$prefix] : [];
        $parts[] = sprintf('Output from process #%d: ', $this->sequence);
        $parts[] = $this->log;

        return sprintf("%s\n", implode("\n", $parts));
    }
}

