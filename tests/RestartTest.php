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
use Composer\XdebugHandler\Tests\Helpers\BaseTestCase;
use Composer\XdebugHandler\Tests\Mocks\CoreMock;
use Composer\XdebugHandler\Tests\Mocks\FailMock;
use Composer\XdebugHandler\Tests\Mocks\PartialMock;
use Composer\XdebugHandler\Tests\Mocks\RequiredMock;

class RestartTest extends BaseTestCase
{
    public function testRestartWhenLoaded(): void
    {
        $loaded = true;
        $this->setArgv();

        $xdebug = CoreMock::createAndCheck($loaded);
        $this->checkRestart($xdebug);

        // Check command
        $xdebug = PartialMock::createAndCheck($loaded);
        $command = implode(' ', $xdebug->getCommand());
        $tmpIni = $xdebug->getTmpIni();

        $pattern = preg_quote(sprintf(' -n -c %s ', $tmpIni), '/');
        $matched = Preg::isMatch('/'.$pattern.'/', $command);
        self::assertTrue($matched);
    }

    public function testRestartWhenModeIsNotOff(): void
    {
        $loaded = [true, 'debug,trace'];

        $xdebug = CoreMock::createAndCheck($loaded);
        $this->checkRestart($xdebug);
        self::assertFalse($xdebug::isXdebugActive());
    }

    public function testNoRestartWhenNotLoaded(): void
    {
        $loaded = false;

        $xdebug = CoreMock::createAndCheck($loaded);
        $this->checkNoRestart($xdebug);
        self::assertFalse($xdebug::isXdebugActive());
    }

    public function testNoRestartWhenLoadedAndAllowed(): void
    {
        $loaded = true;
        putenv(CoreMock::ALLOW_XDEBUG.'=1');

        $xdebug = CoreMock::createAndCheck($loaded);
        $this->checkNoRestart($xdebug);
        self::assertTrue($xdebug::isXdebugActive());
    }

    public function testNoRestartWhenModeIsOff(): void
    {
        $loaded = [true, 'off'];

        $xdebug = CoreMock::createAndCheck($loaded);
        $this->checkNoRestart($xdebug);
        self::assertFalse($xdebug::isXdebugActive());
    }

    public function testFailedRestart(): void
    {
        $loaded = true;

        $xdebug = FailMock::createAndCheck($loaded);
        $this->checkRestart($xdebug);
    }

    public function testNoRestartWithUnexpectedArgv(): void
    {
        $loaded = true;

        $_SERVER['argv'] = false;
        $xdebug = CoreMock::createAndCheck($loaded);
        $this->checkNoRestart($xdebug);

        $_SERVER['argv'] = [];
        $xdebug = CoreMock::createAndCheck($loaded);
        $this->checkNoRestart($xdebug);

        $_SERVER['argv'] = [1, 2];
        $xdebug = CoreMock::createAndCheck($loaded);
        $this->checkNoRestart($xdebug);
    }

    /**
     * @dataProvider unreachableScriptProvider
     */
    public function testNoRestartWithUnreachableScript(string $script): void
    {
        $loaded = true;
        // We can only check this by setting a script
        $settings = ['setMainScript' => [$script]];

        $xdebug = CoreMock::createAndCheck($loaded, null, $settings);
        $this->checkNoRestart($xdebug);
    }

    /**
     * @return array<string[]>
     */
    public static function unreachableScriptProvider(): array
    {
        return [
            ['nonexistent.php'],
            ['-'],
            ['Standard input code'],
        ];
    }

    /**
     * @dataProvider scriptSetterProvider
     */
    public function testRestartWithScriptSetter(string $script): void
    {
        $loaded = true;
        $this->setArgv();
        $settings = ['setMainScript' => [$script]];

        $xdebug = CoreMock::createAndCheck($loaded, null, $settings);
        $this->checkRestart($xdebug);

        // Check command
        $xdebug = PartialMock::createAndCheck($loaded, null, $settings);
        $command = $xdebug->getCommand();

        self::assertContains($script, $command);
    }

    /**
     * @return array<string[]>
     */
    public static function scriptSetterProvider(): array
    {
        return [
            // @phpstan-ignore-next-line
            [(string) realpath($_SERVER['argv'][0])],
            ['--'],
        ];
    }

    public function testSetPersistent(): void
    {
        $loaded = true;
        $this->setArgv();
        $settings = ['setPersistent' => []];

        // Check command
        $xdebug = PartialMock::createAndCheck($loaded, null, $settings);
        $command = $xdebug->getCommand();
        $tmpIni = $xdebug->getTmpIni();

        foreach (['-n', '-c', $tmpIni] as $param) {
            self::assertNotContains($param, $command);
        }
    }

    public function testNoRestartWhenNotRequired(): void
    {
        $loaded = true;
        $required = false;

        $xdebug = RequiredMock::runCoreMock($loaded, $required);
        $this->checkNoRestart($xdebug);
    }

    public function testNoRestartWhenRequiredAndAllowed(): void
    {
        $loaded = true;
        putenv(CoreMock::ALLOW_XDEBUG.'=1');
        $required = true;

        $xdebug = RequiredMock::runCoreMock($loaded, $required);
        $this->checkNoRestart($xdebug);
    }

    /**
     * Sets $_SERVER['argv'] for testing commands
     */
    private function setArgv(): void
    {
        $_SERVER['argv'] = [__FILE__, 'command', '--param1, --param2'];
    }
}
