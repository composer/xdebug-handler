<?php

/*
 * This file is part of composer/xdebug-handler.
 *
 * (c) Composer <https://github.com/composer>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Composer\XdebugHandler\Helpers;

use Composer\XdebugHandler\XdebugHandler;

/**
 * CoreMock provides base functionality for mocking XdebugHandler. It provides
 * it own restart method that mocks a restart by creating a new instance of
 * itself and setting the CoreMock::restarted property to true. Extend this
 * class to provide further capabilities.
 *
 * It does not matter whether xdebug is loaded, because this value is overriden
 * in the constructor.
 *
 * If a temp ini file is created, it is deleted in the destructor.
 */
class CoreMock extends XdebugHandler
{
    const ALLOW_XDEBUG = 'MOCK_ALLOW_XDEBUG';
    const ORIGINAL_INIS = 'MOCK_ORIGINAL_INIS';
    const TEST_VERSION = '2.5.0';

    public $restarted;

    protected $childProcess;
    protected $refClass;

    public static function createAndCheck($loaded, $colorOption = '', $parentProcess = null)
    {
        $xdebug = new static($loaded, strval($colorOption));

        if ($parentProcess) {
            // This is a restart, so set restarted on this instance
            $xdebug->restarted = true;
            $parentProcess->childProcess = $xdebug;
        }

        $xdebug->check();
        return $xdebug->childProcess ?: $xdebug;
    }

    public function __construct($loaded, $colorOption)
    {
        parent::__construct('mock', $colorOption);

        $this->refClass = new \ReflectionClass('Composer\XdebugHandler\XdebugHandler');

        // Set private loaded
        $prop = $this->refClass->getProperty('loaded');
        $prop->setAccessible(true);
        $prop->setValue($this, $loaded);

        // Set private version, based on loaded
        $prop = $this->refClass->getProperty('version');
        $prop->setAccessible(true);
        $version = $loaded ? static::TEST_VERSION : null;
        $prop->setValue($this, $version);

        // Ensure static private skipped is unset
        $prop = $this->refClass->getProperty('skipped');
        $prop->setAccessible(true);
        $prop->setValue($this, null);

        $this->restarted = false;
    }

    public function __destruct()
    {
        // Delete the tmpIni if one has been created
        $prop = $this->refClass->getProperty('tmpIni');
        $prop->setAccessible(true);

        if ($tmpIni = $prop->getValue($this)) {
            @unlink($tmpIni);
        }
    }

    protected function restart($command)
    {
        static::createAndCheck(false, null, $this);
    }
}
