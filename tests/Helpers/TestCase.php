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

use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public static $env = array();

    public static function setUpBeforeClass()
    {
        // Save current state
        $names = array(
            CoreMock::ALLOW_XDEBUG,
            CoreMock::ORIGINAL_INIS,
            'PHP_INI_SCAN_DIR',
        );

        foreach ($names as $name) {
            self::$env[$name] = getenv($name);
        }
    }

    public static function tearDownAfterClass()
    {
        // Restore original state
        foreach (self::$env as $name => $value) {
            if (false !== $value) {
                putenv($name.'='.$value);
            } else {
                putenv($name);
            }
        }
    }

    protected function setUp()
    {
        // Ensure environment variables are unset
        putenv(CoreMock::ALLOW_XDEBUG);
        putenv(CoreMock::ORIGINAL_INIS);
        putenv('PHP_INI_SCAN_DIR');
    }
}
