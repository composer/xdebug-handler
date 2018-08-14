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
use Composer\XdebugHandler\Helpers\Logger;
use Composer\XdebugHandler\Mocks\CoreMock;
use Psr\Log\LogLevel;

/**
 * We use PHP_BINARY which only became available in PHP 5.4
 *
 * @requires PHP 5.4
 */
class StatusTest extends BaseTestCase
{
    public function testSetLoggerProvidesOutput()
    {
        $loaded = true;

        $logger = new Logger();
        $settings = array('setLogger' => array($logger));

        $xdebug = CoreMock::createAndCheck($loaded, null, $settings);
        $this->checkRestart($xdebug);

        $output = $logger->getOutput();
        $this->assertNotEmpty($output);
        $this->checkStatusOutput($output);
    }

    /**
     * Assertions to check the status message and logging formats
     *
     * @param array $output
     */
    protected function checkStatusOutput(array $output)
    {
        $levels = array(LogLevel::DEBUG, LogLevel::WARNING);

        foreach ($output as $record) {
            $this->assertCount(3, $record);
            $this->assertContains($record[0], $levels);
            $this->assertCount(0, $record[2]);
        }
    }
}
