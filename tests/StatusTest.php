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

use Composer\XdebugHandler\Tests\Helpers\BaseTestCase;
use Composer\XdebugHandler\Tests\Helpers\LoggerFactory;
use Composer\XdebugHandler\Tests\Mocks\CoreMock;
use Psr\Log\LogLevel;

/**
 * We use PHP_BINARY which only became available in PHP 5.4
 *
 * @requires PHP 5.4
 */
class StatusTest extends BaseTestCase
{
    /**
     * @return void
     */
    public function testSetLoggerProvidesOutput()
    {
        $loaded = true;

        $logger = LoggerFactory::createLogger();
        $settings = array('setLogger' => array($logger));

        $xdebug = CoreMock::createAndCheck($loaded, null, $settings);
        $this->checkRestart($xdebug);

        $output = $logger->getOutput();
        self::assertNotEmpty($output);
    }
}
