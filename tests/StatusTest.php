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
    public function testVerboseOptionLoaded()
    {
        $loaded = true;
        $_SERVER['argv'][] = '-vvv';

        $xdebug = CoreMock::createAndCheck($loaded);
        $this->checkRestart($xdebug);

        $output = $this->getActualOutput();
        $this->assertNotEmpty($output);
    }

    public function testVerboseOptionNotLoaded()
    {
        $loaded = false;
        $_SERVER['argv'][] = '-vvv';

        $xdebug = CoreMock::createAndCheck($loaded);
        $this->checkNoRestart($xdebug);

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
    }
}
