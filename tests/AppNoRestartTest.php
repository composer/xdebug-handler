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

/**
 * @group xdebug-functional
 */
class AppNoRestartTest extends FunctionalTestCase
{
    /**
     * Tests no restart when env allow is truthy
     *
     * @requires extension xdebug
     */
    public function testNoRestartWhenAllowed(): void
    {
        $script = 'app-basic.php';

        $logs = $this->runner->run($script, null, true);

        $provider = $this->createMessageProvider($script);
        $expected = $provider->getNoRestartMessages('allow');
        $this->checkNoRestart($logs, $expected);
    }

    /**
     * Tests no restart when xdebug.mode is off
     *
     * @requires extension xdebug
     */
    public function testNoRestartWhenModeOff(): void
    {
        if ($this->compareXdebugVersion('3.1', '<', $version)) {
            self::markTestSkipped('Not supported in xdebug version '.$version);
        }

        $script = 'app-basic.php';

        $options = new PhpOptions();
        $options->addPhpArgs('-dxdebug.mode=off');

        $logs = $this->runner->run($script, $options);

        $provider = $this->createMessageProvider($script);
        $expected = $provider->getNoRestartMessages('allow-mode');
        $this->checkNoRestart($logs, $expected);
    }

    /**
     * Tests no restart when proc_open is disabled
     *
     * @requires extension xdebug
     */
    function testNoRestartWhenConfigError(): void
    {
        $script = 'app-basic.php';

        $options = new PhpOptions();
        $options->addPhpArgs('-ddisable_functions=proc_open');

        $logs = $this->runner->run($script, $options);

        $provider = $this->createMessageProvider($script);
        $expected = $provider->getErrorMessages('proc_open function is disabled');
        $this->checkNoRestart($logs, $expected);
    }

    /**
     * Tests no restart when allowed by an application
     *
     * @requires extension xdebug
     */
    function testNoRestartWhenAllowedByApplication(): void
    {
        $script = 'app-extend-allow.php';

        $options = new PhpOptions();
        $options->addScriptArgs('--help');

        $logs = $this->runner->run($script, $options);

        $provider = $this->createMessageProvider($script);
        $expected = $provider->getNoRestartMessages('allow-app');
        $this->checkNoRestart($logs, $expected);
    }
}
