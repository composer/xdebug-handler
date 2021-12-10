<?php

/*
 * This file is part of composer/xdebug-handler.
 *
 * (c) Composer <https://github.com/composer>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Composer\XdebugHandler\Tests\Mocks;

use Composer\XdebugHandler\Tests\Helpers\BaseTestCase;
use Composer\XdebugHandler\XdebugHandler;

/**
 * CoreMock provides base functionality for mocking XdebugHandler, providing its
 * own restart method that mocks a restart by creating a new instance of itself
 * and setting the CoreMock::restarted property to true, and a public
 * getProperty method that accesses private properties. Extend this class to
 * provide further capabilities.
 *
 * It does not matter whether Xdebug is loaded, because test values overwrite
 * runtime values in the constructor.
 *
 * The tmpIni file is deleted in the destructor.
 */
class CoreMock extends XdebugHandler
{
    const ALLOW_XDEBUG = 'MOCK_ALLOW_XDEBUG';
    const ORIGINAL_INIS = 'MOCK_ORIGINAL_INIS';
    const TEST_VERSION = '2.5.0';

    /** @var bool */
    public $restarted;

    /** @var bool */
    public $parentLoaded;

    /** @var null|static */
    protected $childProcess;

    /**
     * @var mixed
     * @phpstan-var \ReflectionClass<\Composer\XdebugHandler\XdebugHandler>
     */
    protected $refClass;

    /**
     * @var array<string, mixed[]>
     */
    protected static $settings;

    /**
     * @param bool|array $loaded
     * @phpstan-param bool|array{0: bool, 1: string} $loaded
     * @param null|static $parentProcess
     * @param array $settings
     * @phpstan-param array<string, mixed[]> $settings
     *
     * @return static
     */
    public static function createAndCheck($loaded, $parentProcess = null, $settings = [])
    {
        $mode = null;

        if (is_array($loaded)) {
            list($loaded, $mode) = $loaded;
        }

        if ($mode !== null && !$loaded) {
            throw new \InvalidArgumentException('Unexpected mode when not loaded: '.$mode);
        }

        $xdebug = new static($loaded, $mode);

        if ($parentProcess !== null) {
            // This is a restart, so we need to set specific testing
            // properties on the parent and child
            $parentProcess->restarted = true;
            $xdebug->restarted = true;
            $xdebug->parentLoaded = $parentProcess->parentLoaded;

            // Make the child available
            $parentProcess->childProcess = $xdebug;

            // Ensure $_SERVER has the restart environment changes
            self::updateServerEnvironment();
        }

        foreach ($settings as $method => $args) {
            BaseTestCase::safeCall($xdebug, $method, $args);
        }

        static::$settings = $settings;

        $xdebug->check();
        return $xdebug->childProcess !==null ? $xdebug->childProcess : $xdebug;
    }

    /**
     *
     * @param bool $loaded
     * @param string|null $mode
     */
    final public function __construct($loaded, $mode)
    {
        parent::__construct('mock');

        $this->refClass = new \ReflectionClass('Composer\XdebugHandler\XdebugHandler');
        $this->parentLoaded = $loaded ? static::TEST_VERSION : null;

        // Set private loaded
        $prop = $this->refClass->getProperty('loaded');
        $prop->setAccessible(true);
        $prop->setValue($this, $this->parentLoaded);

        // Set private mode
        $prop = $this->refClass->getProperty('mode');
        $prop->setAccessible(true);
        $prop->setValue($this, $mode);

        // Set private static xdebugActive
        $prop = $this->refClass->getProperty('xdebugActive');
        $prop->setAccessible(true);
        $prop->setValue($this, $loaded && $mode !== 'off');

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
        if (is_string($this->tmpIni)) {
            @unlink($this->tmpIni);
        }
    }

    /**
     * @param string $name
     *
     * @return mixed
     */
    public function getProperty($name)
    {
        $prop = $this->refClass->getProperty($name);
        $prop->setAccessible(true);
        return $prop->getValue($this);
    }

    /**
     * @param string[] $command
     *
     * @return void
     */
    protected function restart($command)
    {
        static::createAndCheck(false, $this, static::$settings);
    }

    /**
     * @return void
     */
    private static function updateServerEnvironment()
    {
        $names = [
            CoreMock::ALLOW_XDEBUG,
            CoreMock::ORIGINAL_INIS,
            'PHP_INI_SCAN_DIR',
            'PHPRC',
        ];

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
