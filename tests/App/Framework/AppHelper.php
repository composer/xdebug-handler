<?php

declare(strict_types=1);

/*
 * This file is part of composer/xdebug-handler.
 *
 * (c) Composer <https://github.com/composer>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Composer\XdebugHandler\Tests\App\Framework;

use Composer\XdebugHandler\Process;
use Composer\XdebugHandler\XdebugHandler;

class AppHelper extends AppRunner
{
    public const ENV_IS_DISPLAY = 'XDEBUG_HANDLER_TEST_DISPLAY';
    public const ENV_SHOW_INIS = 'XDEBUG_HANDLER_TEST_INIS';

    /** @var string */
    private $appName;
    /** @var string */
    private $envPrefix;
    /** @var non-empty-list<string> */
    private $serverArgv;
    /** @var class-string|null */
    private $className;
    /** @var bool */
    private $display;
    /** @var bool */
    private $showInis;

    /** @var Logger */
    private $logger;

    /** @var Output */
    private $output;

    /** @var Status */
    private $status = null;

    public function __construct(string $script)
    {
        parent::__construct(dirname($script));
        $this->appName = $this->getAppName($script);
        $this->envPrefix = $this->getEnvPrefix($script);

        $this->display = $this->getDisplayFromServer();
        $this->showInis = $this->getInisFromServer();
        $this->setServerArgv();

        $this->output = new Output($this->display);
        $this->logger = new Logger($this->output);
        $this->status = new Status($this->display, $this->showInis);
    }

    /**
     *
     * @return non-empty-list<string>
     */
    public function getServerArgv(): array
    {
        return $this->serverArgv;
    }

    public function getServerArgv0(): string
    {
        return $this->getServerArgv()[0];
    }

    /**
     *
     * @param class-string $class
     * @param array<string, string> $settings
     */
    public function getXdebugHandler(?string $class = null, ?array $settings = null): XdebugHandler
    {
        if ($class === null) {
            $class = XdebugHandler::class;
        } elseif (!is_subclass_of($class, XdebugHandler::class)) {
            throw new \RuntimeException($class.' must extend XdebugHandler');
        }

        $this->className = $class;

        /** @var XdebugHandler $xdebug */
        $xdebug = new $class($this->envPrefix);
        $xdebug->setLogger($this->logger);

        if (isset($settings['mainScript'])) {
            $xdebug->setMainScript($settings['mainScript']);
        }

        if (isset($settings['persistent'])) {
            $xdebug->setPersistent();
        }

        return $xdebug;
    }

    public function runScript(string $script, ?PhpOptions $options = null): void
    {
        parent::runScript($script, $options);
    }

    public function write(string $message): void
    {
        $this->logger->write($message, $this->appName);
    }

    public function writeXdebugStatus(): void
    {
        $className = $this->className ?? XdebugHandler::class;
        $items = $this->status->getWorkingsStatus($className);

        foreach($items as $item) {
            $this->write('working '.$item);
        }
    }

    private function setServerArgv(): void
    {
        $args = [];
        $errors = false;

        if (isset($_SERVER['argv']) && is_array($_SERVER['argv'])) {
            foreach ($_SERVER['argv'] as $value) {
                if (!is_string($value)) {
                    $errors = true;
                    break;
                }

                $args[] = $value;
            }
        }

        if ($errors || count($args) === 0) {
            throw new \RuntimeException('$_SERVER[argv] is not as expected');
        }

        $this->serverArgv = $args;
    }

    private function getDisplayFromServer(): bool
    {
        $result = false;

        if (isset($_SERVER['argv']) && is_array($_SERVER['argv'])) {
            $key = array_search('--display', $_SERVER['argv'], true);

            if ($key !== false) {
                $result = true;
                Process::setEnv(self::ENV_IS_DISPLAY, '1');
                unset($_SERVER['argv'][$key]);
            } else {
                $result = false !== getenv(self::ENV_IS_DISPLAY);
            }
        }

        return $result;
    }

    private function getInisFromServer(): bool
    {
        $result = false;

        if (isset($_SERVER['argv']) && is_array($_SERVER['argv'])) {
            $key = array_search('--inis', $_SERVER['argv'], true);

            if ($key !== false) {
                $result = true;
                Process::setEnv(self::ENV_SHOW_INIS, '1');
                unset($_SERVER['argv'][$key]);
            } else {
                $result = false !== getenv(self::ENV_SHOW_INIS);
            }
        }

        return $result;
    }
}
