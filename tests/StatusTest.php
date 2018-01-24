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
use Composer\XdebugHandler\Mocks\CoreMock;

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

        $logger = new CliLogger();
        $settings = array('setLogger' => array($logger));

        $xdebug = CoreMock::createAndCheck($loaded, null, $settings);
        $this->checkRestart($xdebug);

        $output = $this->getActualOutput();
        $this->assertNotEmpty($output);
    }

    protected function setUp()
    {
        parent::setUp();
        $this->setOutputCallback(array($this, 'emptyOutputCallback'));
    }

    protected function emptyOutputCallback()
    {
        // Noop - needed to suppress the output in PHPUnit
    }
}
