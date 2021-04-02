<?php

/*
 * This file is part of composer/xdebug-handler.
 *
 * (c) Composer <https://github.com/composer>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Composer\XdebugHandler\Tests;

use Composer\Pcre\Preg;
use Composer\XdebugHandler\Process;
use Composer\XdebugHandler\Tests\Helpers\BaseTestCase;
use Composer\XdebugHandler\Tests\Helpers\IniHelper;
use Composer\XdebugHandler\Tests\Mocks\CoreMock;
use Composer\XdebugHandler\Tests\Mocks\PartialMock;

class IniFilesTest extends BaseTestCase
{
    /**
     * Tests that the ini files stored in the _ORIGINAL_INIS environment
     * variable are formatted and reported correctly.
     *
     * @param string $iniFunc IniHelper method to use     *
     * @dataProvider iniFilesProvider
     *
     * @return void
     */
    public function testGetAllIniFiles($iniFunc)
    {
        $ini = new IniHelper();
        BaseTestCase::safeCall($ini, $iniFunc, null, $this);

        $loaded = true;
        $xdebug = CoreMock::createAndCheck($loaded);

        $this->checkRestart($xdebug);
        self::assertEquals($ini->getIniFiles(), CoreMock::getAllIniFiles());
    }

    /**
     * @return array<string, string[]>
     */
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
     * @param string $iniFunc IniHelper method to use
     * @param int $matches The number of disabled entries to match
     * @dataProvider tmpIniProvider
     *
     * @return void
     */
    public function testTmpIni($iniFunc, $matches)
    {
        $ini = new IniHelper();
        BaseTestCase::safeCall($ini, $iniFunc, null, $this);

        $loaded = true;
        $xdebug = PartialMock::createAndCheck($loaded);

        $content = $this->getTmpIniContent($xdebug);
        $regex = '/^\s*;zend_extension\s*=.*xdebug.*$/mi';
        $result = Preg::matchAll($regex, $content);
        self::assertSame($result, $matches);

        // Check content is end-of-line terminated
        $regex = sprintf('/%s/', preg_quote(PHP_EOL));
        self::assertTrue(Preg::isMatch($regex, $content));
    }

    /**
     * @return array<string, mixed[]>
     * @phpstan-return array<string, array{0: string, 1: int}>
     */
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
     *
     * @return void
     */
    public function testMergeInis($name, $value)
    {
        $ini = new IniHelper();
        $ini->setAllInis();

        // Mock user -d setting
        $orig = ini_set($name, $value);

        if (false === $orig) {
            self::fail('Unable to set ini value: '.$name);
        }

        $loaded = true;
        $xdebug = PartialMock::createAndCheck($loaded);
        ini_set($name, $orig);

        $content = $this->getTmpIniContent($xdebug);
        $config = parse_ini_string($content);

        if (false === $config) {
            self::fail('Unable to parse ini content');
        }

        self::assertArrayHasKey($name, $config);
        self::assertEquals($value, $config[$name]);
    }

    /**
     * @return array<string, string[]>
     */
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
     * @return void
     */
    public function testInaccessbleIni()
    {
        $ini = new IniHelper();
        $ini->setInaccessibleIni();

        $loaded = true;
        $xdebug = CoreMock::createAndCheck($loaded);

        // We need to remove the mock inis from the environment
        Process::setEnv(CoreMock::ORIGINAL_INIS, null);
        $this->checkNoRestart($xdebug);
    }

    /**
     * Tests that directives below HOST and PATH sections are removed
     *
     * @dataProvider iniSectionsProvider      *
     * @param string $sectionName
     *
     * @return void
     */
    public function testIniSections($sectionName)
    {
        $ini = new IniHelper();
        $ini->setSectionInis($sectionName);

        $loaded = true;
        $xdebug = PartialMock::createAndCheck($loaded);

        $content = $this->getTmpIniContent($xdebug);
        $config = parse_ini_string($content);

        if (false === $config) {
            self::fail('Unable to parse ini content');
        }

        self::assertArrayHasKey('cli.setting', $config);
        self::assertArrayNotHasKey('cgi.only.setting', $config);
    }

    /**
     * @return array<string, string[]>
     */
    public function iniSectionsProvider()
    {
        return array(
            'host-section' => array('host'),
            'path-section' => array('path'),
        );
    }

    /**
     * Common method to get mocked tmp ini content
     *
     * @return string
     */
    private function getTmpIniContent(PartialMock $xdebug)
    {
        $tmpIni = $xdebug->getTmpIni();

        if ($tmpIni === null) {
            self::fail('The tmpIni file was not created');
        }

        if (!file_exists($tmpIni)) {
            self::fail($tmpIni.' does not exist');
        }

        $content = file_get_contents($tmpIni);

        if (false === $content) {
            self::fail($tmpIni.' cannot be read');
        }

        return $content;
    }
}
