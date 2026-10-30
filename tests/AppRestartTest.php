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
use Composer\XdebugHandler\Tests\App\Framework\Messages;
use Composer\XdebugHandler\Tests\App\Framework\PhpOptions;
use Composer\XdebugHandler\Tests\App\Helpers\FunctionalTestCase;
use PHPUnit\Framework\TestCase;

/**
 * @group xdebug-functional
 */
class AppRestartTest extends FunctionalTestCase
{
    /**
     * Tests basic restart functionality
     *
     * @requires extension xdebug
     */
    public function testRestart(): void
    {
        $script = 'app-basic.php';

        $logs = $this->runner->run($script);

        $provider = $this->createMessageProvider($script);
        [$main, $child] = $provider->getRestartMessages();
        $this->checkRestart($logs, $main, $child);
    }

    /**
     * Tests a restart using an auto_prepend_file
     *
     * @requires extension xdebug
     */
    public function testRestartWithPrependFile(): void
    {
        $script = 'app-plain.php';

        $options = new PhpOptions();
        $options->addPhpArgs('-dauto_prepend_file=app-prepend.php');

        $logs = $this->runner->run($script, $options);

        $provider = $this->createMessageProvider('app-prepend');
        [$main, $child] = $provider->getRestartMessages();
        $this->checkRestart($logs, $main, $child);

        // check app-plain
        $processLog = $logs->getNamed(2, 'app-plain');

        // check not loaded at index 1
        $expected = '[xdebug] extension NOT loaded';
        $this->checkExpectedMessage($processLog, 1, $expected);

        // check settings available at index 2
        $expected = '[settings] available';
        $this->checkExpectedMessage($processLog, 2, $expected);
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

        $provider = $this->createMessageProvider('app-prepend.php');
        [$main, $child] = $provider->getRestartMessages();
        $this->checkRestart($logs, $main, $child);

        // check app-plain
        $processLog = $logs->getNamed(2, 'Standard input code');

        // check not loaded at index 1
        $expected = '[xdebug] extension NOT loaded';
        $this->checkExpectedMessage($processLog, 1, $expected);

        // check settings available at index 2
        $expected = '[settings] available';
        $this->checkExpectedMessage($processLog, 2, $expected);
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

        $provider = $this->createMessageProvider('Standard input code');
        [$main] = $provider->getRestartMessages();
        // app-plain does not implement xdebug-handler so there is no logger output
        $this->checkRestart($logs, $main, []);

        // check app-plain
        $processLog = $logs->getNamed(2, 'app-plain');

        // check not loaded at index 1
        $expected = '[xdebug] extension NOT loaded';
        $this->checkExpectedMessage($processLog, 1, $expected);

        // check settings not available at index 2
        $expected = '[settings] not available';
        $this->checkExpectedMessage($processLog, 2, $expected);
    }

    /**
     *
     * @requires extension xdebug
     */
    public function testRestartWithIniChange(): void
    {
        $script = 'app-extend-ini.php';

        $options = new PhpOptions();
        $options->addPhpArgs('-dphar.readonly=1');

        $logs = $this->runner->run($script, $options);

        // use types to compose the message
        $provider = $this->createMessageProvider($script);
        [$mainTypes, $childTypes] = $provider->getRestartTypes();
        $extras = [];

        $this->modifyTypes($mainTypes, 'check', 'insert-after', 'info1');
        $extras['info1'] = ['phar.readonly=1 detected, restart required'];

        $this->modifyTypes($mainTypes, 'loaded', 'insert-after', 'info2');
        $extras['info2'] = ['phar.readonly=0 added to temporary ini file'];

        $main = $provider->compose($mainTypes, $extras);
        $child = $provider->compose($childTypes);
        $this->checkRestart($logs, $main, $child);

        // check child
        $processLog = $logs->getNamed(2, 'app-extend-ini');

        // check ini setting has changed at index 1
        $expected = 'phar.readonly=0';
        $this->checkExpectedMessage($processLog, 1, $expected);
    }

    /**
     *
     * @requires extension xdebug
     */
    public function testRestartWithXdebugModeChange(): void
    {
        $version = (string) phpversion('xdebug');

        if (version_compare($version, '3.0', '<')) {
            self::markTestSkipped('Not supported in xdebug version '.$version);
        }

        $script = 'app-extend-mode.php';

        $options = new PhpOptions();
        $options->addPhpArgs('-dxdebug.mode=develop');

        $logs = $this->runner->run($script, $options);

        $provider = $this->createMessageProvider($script);
        [$main, $child] = $provider->getRestartMessages();

        // Modify restarted messages
        $child['restarted'][1] = 'The Xdebug extension is loaded';
        $child['restarted'][2] = 'xdebug.mode=coverage';
        $this->checkRestart($logs, $main, $child);
    }

    /**
     *
     * @requires extension xdebug
     */
    public function testRestartChildInStandardConfig(): void
    {
        $script = 'app-standard.php';

        $logs = $this->runner->run($script);

        $provider = $this->createMessageProvider($script);
        [$main, $child] = $provider->getRestartMessages();
        $this->checkRestart($logs, $main, $child);

        // first sub-process - app-plain
        $processLog = $logs->getNamed(3, 'app-plain');

        // check loaded at index 1
        $expected = 'extension loaded';
        $this->checkExpectedMessage($processLog, 1, $expected);

        // check settings not applicable at index 2
        $expected = '[settings] not applicable';
        $this->checkExpectedMessage($processLog, 2, $expected);

        // second sub-process - app-basic
        $processLog = $logs->getDefault(4);

        $provider = $this->createMessageProvider('app-basic');
        $expected = $provider->getExistingMessages();
        $this->checkExpectedMessages($expected, $processLog, 'app-basic');

        // get app-basic logs
        $processLog = $logs->getNamed(4, 'app-basic');

        // check settings available at index 2
        $expected = '[settings] available';
        $this->checkExpectedMessage($processLog, 2, $expected);
    }

    /**
     *
     * @requires extension xdebug
     */
    public function testRestartChildInPersistentConfig(): void
    {
        $script = 'app-persistent.php';

        $logs = $this->runner->run($script);

        $provider = $this->createMessageProvider($script);
        [$main, $child] = $provider->getRestartMessages(true);
        $this->checkRestart($logs, $main, $child);

        // first sub-process - app-plain
        $processLog = $logs->getNamed(3, 'app-plain');

        // check not loaded at index 1
        $expected = 'extension NOT loaded';
        $this->checkExpectedMessage($processLog, 1, $expected);

        // check settings available at index 2
        $expected = '[settings] available';
        $this->checkExpectedMessage($processLog, 2, $expected);

        // second sub-process - app-plain
        $processLog = $logs->getNamed(4, 'app-plain');

        // check loaded at index 1
        $expected = 'extension loaded';
        $this->checkExpectedMessage($processLog, 1, $expected);

        // check settings not applicable at index 2
        $expected = '[settings] not applicable';
        $this->checkExpectedMessage($processLog, 2, $expected);

        // third sub-process - app-basic
        $processLog = $logs->getDefault(5);

        $provider = $this->createMessageProvider('app-basic');
        $expected = $provider->getExistingMessages();
        $this->checkExpectedMessages($expected, $processLog, 'app-basic');

        // get app-basic logs
        $processLog = $logs->getNamed(5, 'app-basic');

        // check settings available at index 2
        $expected = '[settings] available';
        $this->checkExpectedMessage($processLog, 2, $expected);
    }
}
