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
    const INFO = 'Info';
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
     * @param LoggerInterface $logger
     * @param string $loaded The loaded xdebug version
     * @param string $envAllowXdebug Prefixed _ALLOW_XDEBUG name
     */
    public function __construct(LoggerInterface $logger, $loaded, $envAllowXdebug)
    {
        $start = getenv(self::ENV_RESTART);
        putenv(self::ENV_RESTART);
        $this->time = $start ? round((microtime(true) - $start) * 1000) : 0;

        $this->logger = $logger;
        $this->loaded = $loaded;
        $this->envAllowXdebug = $envAllowXdebug;
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
     * Sends a status message to the logger
     *
     * @param string $text
     * @param string $level
     */
    private function output($text, $level = null)
    {
        $this->logger->log($level ?: LogLevel::DEBUG, $text);
    }

    private function reportCheck()
    {
        $this->output('Checking '.$this->envAllowXdebug);
    }

    private function reportError($error)
    {
        $this->output(sprintf("No restart (%s)", $error), LogLevel::WARNING);
    }

    private function reportInfo($info)
    {
        $this->output($info);
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
        $level = $this->loaded ? LogLevel::WARNING : null;
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
