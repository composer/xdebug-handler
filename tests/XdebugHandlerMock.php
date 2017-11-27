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
    public $restarted;
    public $testVersion = '2.5.0';

    public function __construct($loaded = null)
    {
        parent::__construct();

        $loaded = null === $loaded ? true : $loaded;
        $class = new \ReflectionClass(get_parent_class($this));

        $prop = $class->getProperty('loaded');
        $prop->setAccessible(true);
        $prop->setValue($this, $loaded);

        $prop = $class->getProperty('version');
        $prop->setAccessible(true);
        $version = $loaded ? $this->testVersion : '';
        $prop->setValue($this, $version);

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
