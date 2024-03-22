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

use Composer\XdebugHandler\Tests\App\Framework\AppRunner;
use Composer\XdebugHandler\Tests\App\Framework\LogItem;
use Composer\XdebugHandler\Tests\App\Framework\PhpOptions;
use Composer\XdebugHandler\Tests\App\Helpers\FunctionalTestCase;
use PHPUnit\Framework\TestCase;

/**
 * @group functional
 */
class AppRestartTest extends FunctionalTestCase
{
    /**
     *
     * @requires extension xdebug
     */
    public function testRestart(): void
    {
        $script = 'app-basic.php';

        $logs = $this->runner->run($script);
        $this->checkRestart($logs);
    }

    /**
     *
     * @requires extension xdebug
     */
    public function testRestartWithPrependFile(): void
    {
        $script = 'app-plain.php';

        $options = new PhpOptions();
        $options->addPhpArgs('-dauto_prepend_file=app-prepend.php');

        $logs = $this->runner->run($script, $options);
        $this->checkRestart($logs);

        // check app-plain has run
        $appName = $this->runner->getAppName($script);
        $child = $logs->getValuesForProcessName(2, $appName);
        self::assertNotEmpty($child);
    }

    /**
     *
     * @requires extension xdebug
     */
    public function testRestartWithPrependFileStdin(): void
    {
        $script = 'app-plain.php';

        $options = new PhpOptions();
        $options->addPhpArgs('-dauto_prepend_file=app-prepend.php');
        $options->setStdin(true);

        $logs = $this->runner->run($script, $options);
        $this->checkRestart($logs);

        // check app-plain has run
        $appName = 'Standard input code';
        $child = $logs->getValuesForProcessName(2, $appName);
        self::assertNotEmpty($child);
    }

    /**
     *
     * @requires extension xdebug
     */
    public function testRestartWithStdin(): void
    {
        $script = 'app-stdin.php';

        $options = new PhpOptions();
        $options->addPhpArgs('--');
        $options->addScriptArgs('app-plain.php');
        $options->setStdin(true);

        $logs = $this->runner->run($script, $options);
        $this->checkRestart($logs, 0);

        // check not loaded from app-plain
        $appName = $this->runner->getAppName('app-plain.php');
        $child = $logs->getValuesForProcessName(2, $appName);
        $item = $logs->getItemFromList($child, 2);
        self::assertStringContainsString('The Xdebug extension is not loaded', $item);
    }

    /**
     *
     * @requires extension xdebug
     */
    public function testRestartWithIniChange(): void
    {
        $script = 'app-extend-ini.php';

        $options = new PhpOptions();
        $options->addPhpArgs('-dphar.read_only=1');

        $logs = $this->runner->run($script, $options);
        $this->checkRestart($logs);

        // check ini setting has changed in child
        $appName = $this->runner->getAppName($script);
        $child = $logs->getValuesForProcessName(2, $appName);
        $item = $logs->getItemFromList($child, 2);
        self::assertStringContainsString('phar.readonly=0', $item);
    }

    /**
     *
     * @requires extension xdebug
     */
    public function testRestartWithXdebug(): void
    {
        $version = (string) phpversion('xdebug');

        if (version_compare($version, '3.0', '<')) {
            self::markTestSkipped('Not supported in xdebug version '.$version);
        }

        $script = 'app-extend-mode.php';

        $options = new PhpOptions();
        $options->addPhpArgs('-dxdebug.mode=develop');

        $logs = $this->runner->run($script, $options);
        $this->checkRestart($logs, 2);

        $child = $logs->getValuesForProcess(2);
        $item = $logs->getItemFromList($child, 2);
        self::assertStringContainsString('The Xdebug extension is loaded', $item);
        self::assertStringContainsString('xdebug.mode=coverage', $item);
    }

    /**
     *
     * @requires extension xdebug
     */
    public function testRestartWithProcessPersistent(): void
    {
        $script = 'app-persistent.php';

        $logs = $this->runner->run($script);
        $this->checkRestart($logs);

        // first sub-process - check not loaded from app-plain
        $appName = $this->runner->getAppName('app-plain.php');
        $child = $logs->getValuesForProcessName(3, $appName);
        $item = $logs->getItemFromList($child, 2);
        self::assertStringContainsString('The Xdebug extension is not loaded', $item);

        // second sub-process - check loaded from app-plain
        $appName = $this->runner->getAppName('app-plain.php');
        $child = $logs->getValuesForProcessName(4, $appName);
        $item = $logs->getItemFromList($child, 2);
        self::assertStringContainsString('The Xdebug extension is loaded', $item);

        // third sub-process - check not loaded from app-basic
        $child = $logs->getValuesForProcess(5);
        $item = $logs->getItemFromList($child, 2);
        self::assertStringContainsString('The Xdebug extension is not loaded', $item);

        $item = $logs->getItemFromList($child, 3);
        self::assertStringContainsString('Process called with existing restart settings', $item);
    }
}
