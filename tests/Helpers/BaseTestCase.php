<?php

/*
 * This file is part of composer/xdebug-handler.
 *
 * (c) Composer <https://github.com/composer>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

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
     *
     * @beforeClass
     *
     * @return void
     */
    public static function beforeClass()
    {
        foreach (self::$names as $name) {
            self::$env[$name] = getenv($name);
            // Note $_SERVER will already match
        }

        self::$argv = $_SERVER['argv'];
    }

    /**
     * Restores the original environment and argv state
     *
     * @afterClass
     *
     * @return void
     */
    public static function afterClass()
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
    public static function safeCall($instance, $method, array $params = null, $self = null)
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
     *
     * @before
     *
     * @return void
     */
    public function setUpEnvironment()
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
    protected function checkRestart($xdebug)
    {
        // We must have been restarted
        self::assertTrue($xdebug->restarted);

        // Env ALLOW_XDEBUG must be unset
        self::assertSame(false, getenv(CoreMock::ALLOW_XDEBUG));
        self::assertSame(false, isset($_SERVER[CoreMock::ALLOW_XDEBUG]));

        // Env ORIGINAL_INIS must be set and be a string
        self::assertTrue(is_string(getenv(CoreMock::ORIGINAL_INIS)));
        self::assertSame(true, isset($_SERVER[CoreMock::ORIGINAL_INIS]));

        // Skipped version must only be reported if it was unloaded in the restart
        if (!$xdebug->parentLoaded) {
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
        self::assertTrue(is_string(getenv(CoreMock::RESTART_SETTINGS)));
        self::assertSame(true, isset($_SERVER[CoreMock::RESTART_SETTINGS]));

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
    protected function checkNoRestart($xdebug)
    {
        // We must not have been restarted
        self::assertFalse($xdebug->restarted);

        // Env ORIGINAL_INIS must not be set
        self::assertSame(false, getenv(CoreMock::ORIGINAL_INIS));
        self::assertSame(false, isset($_SERVER[CoreMock::ORIGINAL_INIS]));

        // Skipped version must be an empty string
        self::assertSame('', $xdebug::getSkippedVersion());

        // Env RESTART_SETTINGS must not be set
        self::assertSame(false, getenv(CoreMock::RESTART_SETTINGS));
        self::assertSame(false, isset($_SERVER[CoreMock::RESTART_SETTINGS]));

        // Restart settings must be null
        self::assertNull($xdebug::getRestartSettings());
    }
}
