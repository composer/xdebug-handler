<?php

/*
 * This file is part of composer/xdebug-handler.
 *
 * (c) Composer <https://github.com/composer>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Composer\XdebugHandler\Tests\App\Helpers;

use Composer\XdebugHandler\Tests\App\Framework\AppRunner;
use Composer\XdebugHandler\Tests\App\Framework\LogItem;
use Composer\XdebugHandler\Tests\App\Framework\Logs;
use PHPUnit\Framework\TestCase;

abstract class FunctionalTestCase extends TestCase
{
    /** @var AppRunner */
    protected $runner;

    protected function setUp(): void
    {
        $scriptDir = dirname(__DIR__);
        $this->runner = new AppRunner($scriptDir);
    }

    /**
     * @param-out string $actual
     */
    protected function compareXdebugVersion(string $version, string $op, ?string &$actual): bool
    {
        $actual = (string) phpversion('xdebug');
        return version_compare($actual, $version, $op);
    }

    protected function checkRestart(Logs $logs, ?int $childCount = null): void
    {
        $main = $logs->getValuesForProcess(1);
        $child = $logs->getValuesForProcess(2);

        self::assertCount(5, $main);

        $count = $childCount === null ? 2 : $childCount;
        self::assertCount($count, $child);

        if ($childCount === null) {
            $item = $logs->getItemFromList($child, 2);
            self::assertStringContainsString('The Xdebug extension is not loaded', $item);
        }
    }

    protected function checkNoRestart(Logs $logs, ?string $extraExpected = null): void
    {
        $main = $logs->getValuesForProcess(1);
        $child = $logs->getValuesForProcess(2);

        self::assertCount(3, $main);
        self::assertCount(0, $child);

        $item = $logs->getItemFromList($main, 3);
        self::assertStringContainsString('No restart', $item);

        if ($extraExpected !== null) {
            self::assertStringContainsString($extraExpected, $item);
        }
    }
}