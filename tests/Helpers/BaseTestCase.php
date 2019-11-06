<?php

/*
 * This file is part of composer/xdebug-handler.
 *
 * (c) Composer <https://github.com/composer>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Composer\XdebugHandler\Helpers;

use Composer\XdebugHandler\Mocks\CoreMock;
use Composer\XdebugHandler\XdebugHandler;
use PHPUnit\Framework\TestCase;

/**
 * BaseTestCase provides the framework for mock tests by ensuring that core
 * environment variables are unset before each test. It also provides two helper
 * methods to check the state of restarted and non-restarted processes.
 */
abstract class BaseTestCase extends TestCase
{
    private static $env = array();
    private static $argv = array();

    private static $names = array(
        CoreMock::ALLOW_XDEBUG,
        CoreMock::ORIGINAL_INIS,
        'PHP_INI_SCAN_DIR',
        'PHPRC',
        XdebugHandler::RESTART_SETTINGS,
    );

    /**
     * Saves the current environment and argv state
     *
     * @beforeClass
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
     * Unsets environment variables for each test and restores argv
     *
     * @before
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
     * @param mixed $xdebug
     */
    protected function checkRestart($xdebug)
    {
        // We must have been restarted
        $this->assertTrue($xdebug->restarted);

        // Env ALLOW_XDEBUG must be unset
        $this->assertSame(false, getenv(CoreMock::ALLOW_XDEBUG));
        $this->assertSame(false, isset($_SERVER[CoreMock::ALLOW_XDEBUG]));

        // Env ORIGINAL_INIS must be set and be a string
        $this->assertTrue(is_string(getenv(CoreMock::ORIGINAL_INIS)));
        $this->assertSame(true, isset($_SERVER[CoreMock::ORIGINAL_INIS]));

        // Skipped version must only be reported if it was unloaded in the restart
        if (!$xdebug->parentLoaded || $xdebug->getProperty('loaded')) {
            $version = '';
        } else {
            $version = CoreMock::TEST_VERSION;
        }

        $this->assertSame($version, $xdebug::getSkippedVersion());

        // Env RESTART_SETTINGS must be set and be a string
        $this->assertTrue(is_string(getenv(CoreMock::RESTART_SETTINGS)));
        $this->assertSame(true, isset($_SERVER[CoreMock::RESTART_SETTINGS]));

        // Restart settings must be an array
        $this->assertTrue(is_array($xdebug::getRestartSettings()));
    }

    /**
     * Provides basic assertions for a non-restarted process
     *
     * @param mixed $xdebug
     */
    protected function checkNoRestart($xdebug)
    {
        // We must not have been restarted
        $this->assertFalse($xdebug->restarted);

        // Env ORIGINAL_INIS must not be set
        $this->assertSame(false, getenv(CoreMock::ORIGINAL_INIS));
        $this->assertSame(false, isset($_SERVER[CoreMock::ORIGINAL_INIS]));

        // Skipped version must be an empty string
        $this->assertSame('', $xdebug::getSkippedVersion());

        // Env RESTART_SETTINGS must not be set
        $this->assertSame(false, getenv(CoreMock::RESTART_SETTINGS));
        $this->assertSame(false, isset($_SERVER[CoreMock::RESTART_SETTINGS]));

        // Restart settings must be null
        $this->assertNull($xdebug::getRestartSettings());
    }
}
