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

class XdebugHandlerMock extends XdebugHandler
{
    const ALLOW_XDEBUG = 'MOCK_ALLOW_XDEBUG';
    const ORIGINAL_INIS = 'MOCK_ORIGINAL_INIS';

    public $restarted;
    public $testVersion = '2.5.0';

    public function __construct($loaded = null)
    {
        parent::__construct('mock');

        $loaded = null === $loaded ? true : $loaded;
        $class = new \ReflectionClass(get_parent_class($this));

        $prop = $class->getProperty('loaded');
        $prop->setAccessible(true);
        $prop->setValue($this, $loaded);

        $prop = $class->getProperty('version');
        $prop->setAccessible(true);
        $version = $loaded ? $this->testVersion : '';
        $prop->setValue($this, $version);

        // Ensure static value is always cleared
        $prop = $class->getProperty('skipped');
        $prop->setAccessible(true);
        $prop->setValue($this, '');

        $this->restarted = false;
    }

    protected function restart($command)
    {
        $this->restarted = true;
    }
}
