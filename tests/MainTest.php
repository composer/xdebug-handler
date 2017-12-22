<?php

/*
 * This file is part of composer/xdebug-handler.
 *
 * (c) Composer <https://github.com/composer>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Composer\XdebugHandler;

use Composer\XdebugHandler\Helpers\CoreMock;
use Composer\XdebugHandler\Helpers\FailMock;
use Composer\XdebugHandler\Helpers\PartialMock;
use Composer\XdebugHandler\Helpers\TestCase;

/**
 * We use PHP_BINARY which only became available in PHP 5.4
 *
 * @requires PHP 5.4
 */
class MainTest extends TestCase
{
    public function testRestartWhenLoaded()
    {
        $loaded = true;

        $xdebug = CoreMock::createAndCheck($loaded);
        $this->checkRestart($xdebug);
    }

    public function testNoRestartWhenNotLoaded()
    {
        $loaded = false;

        $xdebug = CoreMock::createAndCheck($loaded);
        $this->checkNoRestart($xdebug);
    }

    public function testNoRestartWhenLoadedAndAllowed()
    {
        $loaded = true;
        putenv(CoreMock::ALLOW_XDEBUG.'=1');

        $xdebug = CoreMock::createAndCheck($loaded);
        $this->checkNoRestart($xdebug);
    }

    public function testFailedRestart()
    {
        $loaded = true;

        $xdebug = FailMock::createAndCheck($loaded);
        $this->checkRestart($xdebug);
    }

    public function testEnvAllowForRestart()
    {
        $loaded = true;

        $xdebug = PartialMock::createAndCheck($loaded);
        $params = array(CoreMock::RESTART_ID, '', CoreMock::TEST_VERSION);
        $expected = implode('|', $params);
        $this->assertEquals($expected, getenv(CoreMock::ALLOW_XDEBUG));
    }

    public function testEnvAllowForRestartWithEmptyScanDir()
    {
        $loaded = true;

        $dir = '';
        putenv('PHP_INI_SCAN_DIR='.$dir);

        $xdebug = PartialMock::createAndCheck($loaded);

        $params = array(CoreMock::RESTART_ID, $dir, CoreMock::TEST_VERSION);
        $expected = implode('|', $params);
        $this->assertEquals($expected, getenv(CoreMock::ALLOW_XDEBUG));
    }

    public function testEnvAllowForRestartWithScanDir()
    {
        $loaded = true;

        $dir = '/some/where';
        putenv('PHP_INI_SCAN_DIR='.$dir);

        $xdebug = PartialMock::createAndCheck($loaded);

        $params = array(CoreMock::RESTART_ID, $dir, CoreMock::TEST_VERSION);
        $expected = implode('|', $params);
        $this->assertEquals($expected, getenv(CoreMock::ALLOW_XDEBUG));
    }

    public function testEmptyScanDirAfterRestart()
    {
        $loaded = true;

        putenv('PHP_INI_SCAN_DIR=');
        $xdebug = CoreMock::createAndCheck($loaded);
        $this->assertSame('', getenv('PHP_INI_SCAN_DIR'));
    }

    public function testScanDirAfterRestart()
    {
        $loaded = true;

        $dir = '/some/where';
        putenv('PHP_INI_SCAN_DIR='.$dir);
        $xdebug = CoreMock::createAndCheck($loaded);
        $this->assertEquals($dir, getenv('PHP_INI_SCAN_DIR'));
    }

    /**
     * @expectedException RuntimeException
     *
     */
    public function testThrowsOnEmptyEnvPrefix()
    {
        $xdebug = new XdebugHandler('');
    }

    /**
     * @expectedException RuntimeException
     *
     */
    public function testThrowsOnInvalidEnvPrefix()
    {
        $xdebug = new XdebugHandler(array('name'));
    }

    private function checkRestart($xdebug)
    {
        // We must have been restarted
        $this->assertTrue($xdebug->restarted);

        // Env ALLOW_XDEBUG must be unset
        $this->assertSame(false, getenv(CoreMock::ALLOW_XDEBUG));

        // Env ORIGINAL_INIS must be set and be a string
        $this->assertInternalType('string', getenv(CoreMock::ORIGINAL_INIS));

        // Skipped version must match xdebug version, or '' if restart fails
        $class = get_class($xdebug);
        $version = !strpos($class, 'Fail') ? CoreMock::TEST_VERSION : '';
        $this->assertSame($version, $class::getSkippedVersion());
    }

    private function checkNoRestart($xdebug)
    {
        // We must not have been restarted
        $this->assertFalse($xdebug->restarted);

        // Env ORIGINAL_INIS must not be set
        $this->assertSame(false, getenv(CoreMock::ORIGINAL_INIS));

        // Skipped version must be an empty string
        $class = get_class($xdebug);
        $this->assertSame('', $class::getSkippedVersion());
    }
}
