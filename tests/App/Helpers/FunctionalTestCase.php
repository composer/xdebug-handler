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
use Composer\XdebugHandler\Tests\App\Framework\MessageProvider;
use Composer\XdebugHandler\Tests\App\Framework\ProcessLog;
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

    /**
     * @param Logs $logs
     * @param array<string, string[]> $mainMessages
     * @param array<string, string[]> $childMessages
     * @return void
     */
    protected function checkRestart(Logs $logs, array $mainMessages, array $childMessages): void
    {
        $mainLogs = $logs->getDefault(1);
        $this->checkExpectedMessages($mainMessages, $mainLogs, 'main');

        $childLogs = $logs->getDefault(2);
        $this->checkExpectedMessages($childMessages, $childLogs, 'child');
    }

    /**
     * @param Logs $logs
     * @param array<string, string[]> $messages
     * @return void
     */
    protected function checkNoRestart(Logs $logs, array $messages): void
    {
        $main = $logs->getDefault(1);
        $this->checkExpectedMessages($messages, $main, 'main');

        $child = $logs->getDefault(2);
        self::assertCount(0, $child->getItems());
    }

    /**
     * @param array<string, string[]> $expected
     * @param ProcessLog $log
     * @param string $name
     * @return void
     */
    protected function checkExpectedMessages(array $expected, ProcessLog $log, string $name): void
    {
        $assertMessage = $log->getOutputInfo($name);
        self::assertCount(count($expected), $log->getItems(), $assertMessage);
        $index = 0;

        foreach ($expected as $type => $messages) {
            $item = $log->getItem($index);
            $assertMessage = $log->getOutputInfo(sprintf('%s: %s', $name, $type));
            $index += 1;

            foreach ($messages as $message) {
                self::assertStringContainsString($message, $item->value, $assertMessage);
            }
        }
    }

    protected function checkExpectedMessage(ProcessLog $log, int $index, string $expected): void
    {
        $message = $log->getOutputInfo();
        $item = $log->getItem($index);
        self::assertStringContainsString($expected, $item->value, $message);
    }

    protected function createMessageProvider(string $script): MessageProvider
    {
        $envAllow = $this->runner->getEnvAllow($script);
        return new MessageProvider($envAllow);
    }
}
