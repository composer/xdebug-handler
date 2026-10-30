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

/**
 * This class runs and provides functionality for the xdebug-handler functional
 * test apps.
 *
 * It sets up the logger and outputs debug and status messages to stdout.
 *
 * The test apps can be run from the command-line and will show detailed output
 * with the --display and --inis options.
 */
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

    /** @var Logger */
    private $logger;

    /** @var Output */
    private $output;

    /** @var Status */
    private $status;

    public function __construct(string $script)
    {
        parent::__construct(dirname($script));
        $this->appName = $this->getAppName($script);
        $this->envPrefix = $this->getEnvPrefix($script);

        [$display, $showInis] = $this->setServerArgvAndOptions();

        $this->output = new Output($display);
        $this->logger = new Logger($this->output);
        $this->status = new Status($display, $showInis);
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
     * Starts an instance of XdebugHandler
     *
     * @param class-string $class Extending class name
     * @param array<string, string> $settings XdebugHandler settings
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

    /**
     * Runs a PHP sub-process
     *
     * @param string $script The file to run
     * @param PhpOptions $options
     */
    public function runScript(string $script, ?PhpOptions $options = null): void
    {
        $script = $this->checkScript($script);

        if ($options === null) {
            $options = new PhpOptions();
        }

        // set passthru in child proccesses so output gets collected
        $options->setPassthru(true);
        $this->phpExecutor->run($script, $options, $this->scriptDir);
    }

    /**
     * Outputs a message prefixed appname[pid] to stdout
     *
     * @param string $message
     */
    public function write(string $message): void
    {
        $this->logger->write($message, $this->appName);
    }

    /**
     * Outputs the Xdebug loaded status to stdout
     *
     * More details can be shown with the --display and --inis options.
     */
    public function writeXdebugStatus(): void
    {
        $className = $this->className ?? XdebugHandler::class;
        $items = $this->status->getWorkingsStatus($className);

        foreach($items as $item) {
            $this->write('working '.$item);
        }
    }

    /**
     * Checks $_SERVER['argv'] and returns $display and $showInis values
     *
     * The flags to display the full status and ini file list are passed on the
     * command-line, then removed and set in the environment.
     *
     * @return array<bool, bool>
     */
    private function setServerArgvAndOptions(): array
    {
        $args = [];
        $errors = false;
        $display = false;
        $showInis = false;

        if (isset($_SERVER['argv']) && is_array($_SERVER['argv'])) {
            foreach ($_SERVER['argv'] as $value) {
                if (!is_string($value)) {
                    $errors = true;
                    break;
                }

                if ($value === '--display') {
                    $display = true;
                    Process::setEnv(self::ENV_IS_DISPLAY, '1');
                    continue;
                }

                if ($value === '--inis') {
                    $showInis = true;
                    Process::setEnv(self::ENV_SHOW_INIS, '1');
                    continue;
                }

                $args[] = $value;
            }
        }

        if ($errors || count($args) === 0) {
            throw new \RuntimeException('$_SERVER[argv] is not as expected');
        }

        $_SERVER['argv'] = $args;
        $this->serverArgv = $args;

        return [
            $display || false !== getenv(self::ENV_IS_DISPLAY),
            $showInis || false !== getenv(self::ENV_SHOW_INIS),
        ];
    }
}
