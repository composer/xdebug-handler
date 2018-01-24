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

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * @author John Stevenson <john-stevenson@blueyonder.co.uk>
 */
class Status
{
    const ENV_RESTART = 'XDEBUG_HANDLER_RESTART';
    const CHECK = 'Check';
    const ERROR = 'Error';
    const NORESTART = 'NoRestart';
    const RESTART = 'Restart';
    const RESTARTING = 'Restarting';
    const RESTARTED = 'Restarted';

    private $envAllowXdebug;
    private $loaded;
    private $logger;
    private $time;

    /**
     * Constructor
     *
     * @param string $loaded The loaded xdebug version
     * @param string $envAllowXdebug Prefixed _ALLOW_XDEBUG name
     * @param integer $time Elapsed restart time
     * @param LoggerInterface $logger Optional logger to replace stdout output
     */
    public function __construct($loaded, $envAllowXdebug, $time, LoggerInterface $logger = null)
    {
        $this->loaded = $loaded;
        $this->envAllowXdebug = $envAllowXdebug;
        $this->time = $time;
        $this->logger = $logger;
    }

    /**
     * Calls a handler method to report a message
     *
     * @param string $op The handler constant
     * @param null|string $data Data required by the handler
     */
    public function report($op, $data)
    {
        $func = array($this, 'report'.$op);
        call_user_func($func, $data);
    }

    /**
     * Prints or logs a status message
     *
     * @param string $text
     * @param string $level
     */
    private function output($text, $level = null)
    {
        $text = sprintf('xdebug-handler: %s', $text);

        if ($this->logger) {
            $level = $level ?: LogLevel::INFO;
            $this->logger->log($level, $text);
        } else {
            print($text.PHP_EOL);
        }
    }

    private function reportCheck()
    {
        $this->output('Checking '.$this->envAllowXdebug);
    }

    private function reportError($error)
    {
        $this->output(sprintf("No restart (%s)", $error), LogLevel::ERROR);
    }

    private function reportNoRestart()
    {
        $this->output($this->getLoadedMessage());

        if ($this->loaded) {
            $text = sprintf("No restart (%s)", $this->getEnvAllow());
            $this->output($text);
        }
    }

    private function reportRestart()
    {
        $this->output($this->getLoadedMessage());
        putenv(self::ENV_RESTART.'='.strval(microtime(true)));
    }

    private function reportRestarted()
    {
        $loaded = $this->getLoadedMessage();
        $text = sprintf('Restarted (%d ms). %s', $this->time, $loaded);
        $level = $this->loaded ? LogLevel::ERROR : null;
        $this->output($text, $level);
    }

    private function reportRestarting()
    {
        $text = sprintf("Process restarting (%s)", $this->getEnvAllow());
        $this->output($text);
    }

    /**
     * Returns the _ALLOW_XDEBUG environment variable as name=value
     *
     * @return string
     */
    private function getEnvAllow()
    {
        return $this->envAllowXdebug.'='.getenv($this->envAllowXdebug);
    }

    /**
     * Returns the xdebug status and version
     *
     * @return string
     */
    private function getLoadedMessage()
    {
        $loaded = $this->loaded ? sprintf('loaded (%s)', $this->loaded) : 'not loaded';
        return 'The xdebug extension is '.$loaded;
    }
}
