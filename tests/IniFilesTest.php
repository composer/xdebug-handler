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
     * @dataProvider iniFilesProvider
     */
    public function testGetAllIniFiles(string $iniFunc): void
    {
        $ini = new IniHelper();
        BaseTestCase::safeCall($ini, $iniFunc, null, $this);

        $loaded = true;
        $xdebug = CoreMock::createAndCheck($loaded);

        $this->checkRestart($xdebug);
        self::assertSame($ini->getIniFiles(), CoreMock::getAllIniFiles());
    }

    /**
     * @return array<string, string[]>
     */
    public static function iniFilesProvider(): array
    {
        // $iniFunc
        return [
            'no-inis' => ['setNoInis'],
            'loaded-ini' => ['setLoadedIni'],
            'scanned-inis' => ['setScannedInis'],
            'all-inis' => ['setAllInis'],
        ];
    }

    /**
     * Tests that the tmpIni file is created, contains disabled Xdebug
     * entries and is correctly end-of-line terminated.
     *
     * @dataProvider tmpIniProvider
     */
    public function testTmpIni(string $iniFunc, int $matchCount): void
    {
        $ini = new IniHelper();
        BaseTestCase::safeCall($ini, $iniFunc, null, $this);

        $loaded = true;
        $xdebug = PartialMock::createAndCheck($loaded);

        $content = $this->getTmpIniContent($xdebug);
        $regex = '/^\s*;zend_extension\s*=.*xdebug.*$/mi';
        $result = Preg::matchAll($regex, $content);
        self::assertSame($result, $matchCount);

        // Check content is end-of-line terminated
        $regex = sprintf('/%s/', preg_quote(PHP_EOL));
        self::assertTrue(Preg::isMatch($regex, $content));
    }

    /**
     * @phpstan-return array<string, array{0: string, 1: int}>
     */
    public static function tmpIniProvider(): array
    {
        // $iniFunc, $matchCount (number of disabled entries)
        return [
            'no-inis' => ['setNoInis', 0],
            'loaded-ini' => ['setLoadedIni', 1],
            'scanned-inis' => ['setScannedInis', 1],
            'all-inis' => ['setAllInis', 2],
        ];
    }

    /**
     * Tests that changed values are added correctly in the tmp ini
     *
     * @dataProvider mergeIniProvider
     */
    public function testMergeInis(string $name, string $value): void
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
        self::assertSame($value, $config[$name]);
    }

    /**
     * @return array<string, string[]>
     */
    public static function mergeIniProvider(): array
    {
        // $name, $value
        return [
            'simple' => ['date.timezone', 'Antarctica/McMurdo'],
            'single-quotes' => ['error_append_string', "<'color'>"],
            'newline' => ['error_append_string', "<color\n>"],
            'double-quotes' => ['error_append_string', '<style="color">'],
            'backslashes' => ['error_append_string', '<style=\\\\\\"color\\\\">'],
        ];
    }

    /**
     * Tests that an inaccessible ini file causes the restart to fail
     */
    public function testInaccessbleIni(): void
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
     * @dataProvider iniSectionsProvider
     */
    public function testIniSections(string $sectionName): void
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
    public static function iniSectionsProvider(): array
    {
        return [
            'host-section' => ['host'],
            'path-section' => ['path'],
        ];
    }

    /**
     * Common method to get mocked tmp ini content
     */
    private function getTmpIniContent(PartialMock $xdebug): string
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
