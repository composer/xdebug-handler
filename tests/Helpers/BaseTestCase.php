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

namespace Composer\XdebugHandler\Tests\Helpers;

use Composer\XdebugHandler\Tests\Mocks\CoreMock;
use Composer\XdebugHandler\Tests\Mocks\FailMock;
use Composer\XdebugHandler\XdebugHandler;
use PHPUnit\Framework\TestCase;

/**
 * BaseTestCase provides the framework for mock tests by ensuring that core
 * environment variables are unset before each test. It also provides two helper
 * methods to check the state of restarted and non-restarted processes.
 */
abstract class BaseTestCase extends TestCase
{
    /**
     * @var array
     * @phpstan-var array<string, string|false>
     */
    private static $env = [];

    /** @var string[] */
    private static $argv = [];

    /** @var string[] */
    private static $names = [
        CoreMock::ALLOW_XDEBUG,
        CoreMock::ORIGINAL_INIS,
        'PHP_INI_SCAN_DIR',
        'PHPRC',
        XdebugHandler::RESTART_SETTINGS,
    ];

    /**
     * Saves the current environment and argv state
     */
    public static function setUpBeforeClass(): void
    {
        foreach (self::$names as $name) {
            self::$env[$name] = getenv($name);
            // Note $_SERVER will already match
        }

        // @phpstan-ignore-next-line
        self::$argv = $_SERVER['argv'];
    }

    /**
     * Restores the original environment and argv state
     */
    public static function tearDownAfterClass(): void
    {
        foreach (self::$env as $name => $value) {
            if (false !== $value) {
                putenv($name.'='.$value);
                $_SERVER[$name] = $value;
            } else {
                putenv($name);
                unset($_SERVER[$name]);
            }
        }

        $_SERVER['argv'] = self::$argv;
    }

    /**
     * @param mixed $instance
     * @param string $method
     * @param mixed[] $params
     * @param null|self $self
     *
     * @return mixed
     */
    public static function safeCall($instance, string $method, ?array $params = null, ?self $self = null)
    {
        $callable = [$instance, $method];
        $params = $params !== null ? $params : [];

        if (is_callable($callable)) {
            return call_user_func_array($callable, $params);
        }

        if ($self !== null) {
            self::fail('Unable to call method: '. $method);
        }

        throw new \LogicException('Unable to call method: '. $method);
    }

    /**
     * Unsets environment variables for each test and restores argv
     */
    protected function setUp(): void
    {
        foreach (self::$names as $name) {
            putenv($name);
            unset($_SERVER[$name]);
        }

        $_SERVER['argv'] = self::$argv;
    }

    /**
     * Provides basic assertions for a restarted process
     *
     * @param \Composer\XdebugHandler\Tests\Mocks\CoreMock $xdebug
     *
     * @return void
     */
    protected function checkRestart(CoreMock $xdebug)
    {
        // We must have been restarted
        self::assertTrue($xdebug->restarted);

        // Env ALLOW_XDEBUG must be unset
        self::assertFalse(getenv(CoreMock::ALLOW_XDEBUG));
        self::assertArrayNotHasKey(CoreMock::ALLOW_XDEBUG, $_SERVER);

        // Env ORIGINAL_INIS must be set and be a string
        self::assertIsString(getenv(CoreMock::ORIGINAL_INIS));
        self::assertArrayHasKey(CoreMock::ORIGINAL_INIS, $_SERVER);

        // Skipped version must only be reported if it was unloaded in the restart
        if ($xdebug->parentXdebugVersion === null) {
            // Mocked successful restart without Xdebug
            $version = '';
        } elseif ($xdebug instanceof FailMock) {
            // Mocked failed restart, with Xdebug still loaded
            $version = '';
        } else {
            $version = CoreMock::TEST_VERSION;
        }

        self::assertSame($version, $xdebug::getSkippedVersion());

        // Env RESTART_SETTINGS must be set and be a string
        self::assertIsString(getenv(CoreMock::RESTART_SETTINGS));
        self::assertArrayHasKey(CoreMock::RESTART_SETTINGS, $_SERVER);

        // Restart settings must be an array
        self::assertTrue(is_array($xdebug::getRestartSettings()));
    }

    /**
     * Provides basic assertions for a non-restarted process
     *
     * @param \Composer\XdebugHandler\Tests\Mocks\CoreMock $xdebug
     *
     * @return void
     */
    protected function checkNoRestart(CoreMock $xdebug)
    {
        // We must not have been restarted
        self::assertFalse($xdebug->restarted);

        // Env ORIGINAL_INIS must not be set
        self::assertFalse(getenv(CoreMock::ORIGINAL_INIS));
        self::assertArrayNotHasKey(CoreMock::ORIGINAL_INIS, $_SERVER);

        // Skipped version must be an empty string
        self::assertSame('', $xdebug::getSkippedVersion());

        // Env RESTART_SETTINGS must not be set
        self::assertFalse(getenv(CoreMock::RESTART_SETTINGS));
        self::assertArrayNotHasKey(CoreMock::RESTART_SETTINGS, $_SERVER);

        // Restart settings must be null
        self::assertNull($xdebug::getRestartSettings());
    }
}
