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

    /** @var string|null */
    public $parentXdebugVersion;

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
     * @phpstan-param array<string, mixed[]> $settings
     *
     * @return static
     */
    public static function createAndCheck($loaded, ?self $parentProcess = null, array $settings = array()): self
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
            $xdebug->parentXdebugVersion = $parentProcess->parentXdebugVersion;

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

    final public function __construct(bool $loaded, ?string $mode)
    {
        parent::__construct('mock');

        $this->refClass = new \ReflectionClass('Composer\XdebugHandler\XdebugHandler');
        $this->parentXdebugVersion = $loaded ? static::TEST_VERSION : null;

        // Set private static xdebugVersion
        $prop = $this->refClass->getProperty('xdebugVersion');
        $prop->setAccessible(true);
        $prop->setValue($this, $this->parentXdebugVersion);

        // Set private static xdebugMode
        $prop = $this->refClass->getProperty('xdebugMode');
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
     * @return mixed
     */
    public function getProperty(string $name)
    {
        $prop = $this->refClass->getProperty($name);
        $prop->setAccessible(true);
        return $prop->getValue($this);
    }

    /**
     * @param string[] $command
     */
    protected function restart(array $command): void
    {
        static::createAndCheck(false, $this, static::$settings);
    }

    private static function updateServerEnvironment(): void
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
