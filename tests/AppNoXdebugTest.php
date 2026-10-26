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
use Composer\XdebugHandler\Tests\App\Framework\Messages;
use Composer\XdebugHandler\Tests\App\Framework\PhpOptions;
use Composer\XdebugHandler\Tests\App\Helpers\FunctionalTestCase;
use PHPUnit\Framework\TestCase;

class AppNoXdebugTest extends FunctionalTestCase
{
    /**
     * Tests no restart when xdebug is not loaded
     */
    public function testNoRestartWithNoXdebug(): void
    {
        if (extension_loaded('xdebug')) {
            self::markTestSkipped('xdebug must not be loaded for this test');
        }

        $script = 'app-basic.php';

        $logs = $this->runner->run($script);

        $provider = $this->createMessageProvider($script);
        $expected = $provider->compose(['check', 'not-loaded']);
        $this->checkNoRestart($logs, $expected);
    }

    /**
     * Tests a forced restart when xdebug is not loaded
     */
    public function testForcedRestartWithNoXdebug(): void
    {
        if (extension_loaded('xdebug')) {
            self::markTestSkipped('xdebug must not be loaded for this test');
        }

        $script = 'app-extend-ini.php';

        $options = new PhpOptions();
        $options->addPhpArgs('-dphar.readonly=1');

        $logs = $this->runner->run($script, $options);

        $provider = $this->createMessageProvider($script);
        [$mainTypes, $childTypes] = $provider->getRestartTypes();

        $index = array_search('loaded', $mainTypes, true);
        if (is_int($index)) {
            $mainTypes[$index] = 'not-loaded';
        }

        $main = $provider->compose($mainTypes);
        $child = $provider->compose($childTypes);
        $this->checkRestart($logs, $main, $child);

        // check child
        $processLog = $logs->getNamed(2, 'app-extend-ini');

        // check ini setting has changed at index 1
        $expected = 'phar.readonly=0';
        $this->checkExpectedMessage($processLog, 1, $expected);
    }
}
