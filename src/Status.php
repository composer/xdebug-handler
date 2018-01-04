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

/**
 * @author John Stevenson <john-stevenson@blueyonder.co.uk>
 */
class Status
{
    const ENV_RESTART = 'XDEBUG_HANDLER_RESTART';
    const ERROR = 'Error';
    const NORESTART = 'NoRestart';
    const RESTART = 'Restart';
    const RESTARTING = 'Restarting';
    const RESTARTED = 'Restarted';

    private $envAllowXdebug;
    private $loaded;
    private $time;

    /**
     * Constructor
     *
     * @param string $loaded The loaded xdebug version
     * @param string $envAllowXdebug Prefixed _ALLOW_XDEBUG name
     * @param integer $time Elapsed restart time
     */
    public function __construct($loaded, $envAllowXdebug, $time)
    {
        $this->loaded = $loaded;
        $this->envAllowXdebug = $envAllowXdebug;
        $this->time = $time;
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
     * Prints a status message line
     *
     * @param string $text
     */
    private function output($text)
    {
        $format = 'xdebug-handler: %s%s';
        printf($format, $text, PHP_EOL);
    }

    private function reportError($error)
    {
        $this->output(sprintf("No restart (%s)", $error));
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
        putenv(self::ENV_RESTART.'='.strval(microtime(true)));
        $this->output($this->getLoadedMessage());
    }

    private function reportRestarted()
    {
        $loaded = $this->getLoadedMessage();
        $text = sprintf('Restarted (%d ms). %s', $this->time, $loaded);
        $this->output($text);
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
