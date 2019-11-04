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

use Composer\XdebugHandler\Helpers\BaseTestCase;
use Composer\XdebugHandler\Helpers\IniHelper;
use Composer\XdebugHandler\Mocks\CoreMock;
use Composer\XdebugHandler\Mocks\PartialMock;

/**
 * We use PHP_BINARY which only became available in PHP 5.4
 *
 * @requires PHP 5.4
 */
class IniFilesTest extends BaseTestCase
{
    /**
     * Tests that the ini files stored in the _ORIGINAL_INIS environment
     * variable are formatted and reported correctly.
     *
     * @param callable $iniFunc IniHelper method to use
     *
     * @dataProvider iniFilesProvider
     */
    public function testGetAllIniFiles($iniFunc)
    {
        $ini = new IniHelper();
        call_user_func(array($ini, $iniFunc));

        $loaded = true;
        $xdebug = CoreMock::createAndCheck($loaded);

        $this->checkRestart($xdebug);
        $this->assertEquals($ini->getIniFiles(), CoreMock::getAllIniFiles());
    }

    public function iniFilesProvider()
    {
        // $iniFunc
        return array(
            'no-inis' => array('setNoInis'),
            'loaded-ini' => array('setLoadedIni'),
            'scanned-inis' => array('setScannedInis'),
            'all-inis' => array('setAllInis'),
        );
    }

    /**
     * Tests that the tmpIni file is created, contains disabled Xdebug
     * entries and is correctly end-of-line terminated.
     *
     * @param callable $iniFunc IniHelper method to use
     * @param int $matches The number of disabled entries to match
     * @dataProvider tmpIniProvider
     */
    public function testTmpIni($iniFunc, $matches)
    {
        $ini = new IniHelper();
        call_user_func(array($ini, $iniFunc));

        $loaded = true;
        $xdebug = PartialMock::createAndCheck($loaded);

        $content = $this->getTmpIniContent($xdebug);
        $regex = '/^\s*;zend_extension\s*=.*xdebug.*$/mi';
        $result = preg_match_all($regex, $content);
        $this->assertSame($result, $matches);

        // Check content is end-of-line terminated
        $regex = sprintf('/%s/', preg_quote(PHP_EOL));
        $this->assertTrue((bool) preg_match($regex, $content));
    }

    public function tmpIniProvider()
    {
        // $iniFunc, $matches (number of disabled entries)
        return array(
            'no-inis' => array('setNoInis', 0),
            'loaded-ini' => array('setLoadedIni', 1),
            'scanned-inis' => array('setScannedInis', 1),
            'all-inis' => array('setAllInis', 2),
        );
    }

    /**
     * Tests that changed values are added correctly in the tmp ini
     *
     * @param string $name Ini setting name
     * @param string $value Ini setting value
     * @dataProvider mergeIniProvider
     */
    public function testMergeInis($name, $value)
    {
        $ini = new IniHelper();
        $ini->setAllInis();

        // Mock user -d setting
        $orig = ini_set($name, $value);

        $loaded = true;
        $xdebug = PartialMock::createAndCheck($loaded);
        ini_set($name, $orig);

        $content = $this->getTmpIniContent($xdebug);
        $config = parse_ini_string($content);
        $this->assertArrayHasKey($name, $config);
        $this->assertEquals($value, $config[$name]);
    }

    public function mergeIniProvider()
    {
        // $name, $value
        return array(
            'simple' => array('date.timezone', 'Antarctica/McMurdo'),
            'single-quotes' => array('error_append_string', "<'color'>"),
            'newline' => array('error_append_string', "<color\n>"),
            'double-quotes' => array('error_append_string', '<style="color">'),
            'backslashes' => array('error_append_string', '<style=\\\\\\"color\\\\">'),
        );
    }

    /**
     * Tests that an inaccessible ini file causes the restart to fail
     *
     */
    public function testInaccessbleIni()
    {
        $ini = new IniHelper();
        $ini->setInaccessibleIni();

        $loaded = true;
        $xdebug = CoreMock::createAndCheck($loaded);

        // We need to remove the mock inis from the environment
        Process::setEnv(CoreMock::ORIGINAL_INIS, false);
        $this->checkNoRestart($xdebug);
    }

    /**
     * Common method to get mocked tmp ini content
     *
     * @param mixed $xdebug
     */
    private function getTmpIniContent(PartialMock $xdebug)
    {
        $tmpIni = $xdebug->getTmpIni();

        if (!$tmpIni) {
            $this->fail('The tmpIni file was not created');
        }

        if (!file_exists($tmpIni)) {
            $this->fail($tmpIni.' does not exist');
        }

        return file_get_contents($tmpIni);
    }
}
