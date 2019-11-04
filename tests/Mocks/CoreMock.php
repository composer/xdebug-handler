<?php

/*
 * This file is part of composer/xdebug-handler.
 *
 * (c) Composer <https://github.com/composer>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Composer\XdebugHandler\Mocks;

use Composer\XdebugHandler\XdebugHandler;

/**
 * CoreMock provides base functionality for mocking XdebugHandler, providing its
 * own restart method that mocks a restart by creating a new instance of itself
 * and setting the CoreMock::restarted property to true, and a public
 * getProperty method that accesses private properties. Extend this class to
 * provide further capabilities.
 *
 * It does not matter whether Xdebug is loaded, because this value is overriden
 * in the constructor.
 *
 * The tmpIni file is deleted in the destructor.
 */
class CoreMock extends XdebugHandler
{
    const ALLOW_XDEBUG = 'MOCK_ALLOW_XDEBUG';
    const ORIGINAL_INIS = 'MOCK_ORIGINAL_INIS';
    const TEST_VERSION = '2.5.0';

    public $restarted;
    public $parentLoaded;

    protected $childProcess;
    protected $refClass;
    protected static $settings;

    public static function createAndCheck($loaded, $parentProcess = null, $settings = array())
    {
        $xdebug = new static($loaded);

        if ($parentProcess) {
            // This is a restart, so set restarted on parent so it is copied
            $parentProcess->restarted = true;

            // Copy all public properties
            $refClass = new \ReflectionClass($parentProcess);
            $props = $refClass->getProperties(\ReflectionProperty::IS_PUBLIC);

            foreach ($props as $prop) {
                $xdebug->{$prop->name} = $parentProcess->{$prop->name};
            }

            $parentProcess->childProcess = $xdebug;
            // Ensure $_SERVER has our environment changes
            static::updateServerEnvironment();
        }

        foreach ($settings as $method => $args) {
            call_user_func_array(array($xdebug, $method), $args);
        }

        static::$settings = $settings;

        $xdebug->check();
        return $xdebug->childProcess ?: $xdebug;
    }

    public function __construct($loaded)
    {
        parent::__construct('mock');

        $this->refClass = new \ReflectionClass('Composer\XdebugHandler\XdebugHandler');
        $this->parentLoaded = $loaded ? static::TEST_VERSION : null;

        // Set private loaded
        $prop = $this->refClass->getProperty('loaded');
        $prop->setAccessible(true);
        $prop->setValue($this, $this->parentLoaded);

        // Ensure static private skipped is unset
        $prop = $this->refClass->getProperty('skipped');
        $prop->setAccessible(true);
        $prop->setValue($this, null);

        // Ensure static private inRestart is unset
        $prop = $this->refClass->getProperty('inRestart');
        $prop->setAccessible(true);
        $prop->setValue($this, null);

        $this->restarted = false;
    }

    public function __destruct()
    {
        // Delete the tmpIni if one has been created
        if (!empty($this->tmpIni)) {
            @unlink($this->tmpIni);
        }
    }

    public function getProperty($name)
    {
        $prop = $this->refClass->getProperty($name);
        $prop->setAccessible(true);
        return $prop->getValue($this);
    }

    protected function restart($command)
    {
        static::createAndCheck(false, $this, static::$settings);
    }

    private static function updateServerEnvironment()
    {
        $names = array(
            CoreMock::ALLOW_XDEBUG,
            CoreMock::ORIGINAL_INIS,
            'PHP_INI_SCAN_DIR',
            'PHPRC',
        );

        foreach ($names as $name) {
            $value = getenv($name);
            if (false === $value) {
                unset($_SERVER[$name]);
            } else {
                $_SERVER[$name] = $value;
            }
        }
    }
}
