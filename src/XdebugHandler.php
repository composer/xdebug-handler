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
class XdebugHandler
{
    const SUFFIX_ALLOW = '_ALLOW_XDEBUG';
    const SUFFIX_INIS = '_ORIGINAL_INIS';
    const RESTART_ID = 'internal';

    private static $name;
    private static $skipped;

    private $cli;
    private $colorOption;
    private $envAllowXdebug;
    private $envOriginalInis;
    private $loaded;
    private $tmpIni;
    private $writer;

    /**
     * Constructor
     *
     * The $envPrefix is used to create distinct environment variables. It is
     * uppercased and prepended to the default base values. For example 'myapp'
     * would result in MYAPP_ALLOW_XDEBUG and MYAPP_ORIGINAL_INIS.
     *
     * @param string $envPrefix Value used in environment variables
     * @param string $colorOption Command-line long option to force color output
     * @throws RuntimeException If $envPrefix is not a non-empty string
     */
    public function __construct($envPrefix, $colorOption = '')
    {
        if (!is_string($envPrefix) || empty($envPrefix) || !is_string($colorOption)) {
            throw new \RuntimeException('Invalid constructor parameter');
        }

        self::$name = strtoupper($envPrefix);
        $this->envAllowXdebug = self::$name.self::SUFFIX_ALLOW;
        $this->envOriginalInis = self::$name.self::SUFFIX_INIS;

        $this->colorOption = $colorOption;
        $this->cli = PHP_SAPI === 'cli';

        if (extension_loaded('xdebug')) {
            $ext = new \ReflectionExtension('xdebug');
            $this->loaded = $ext->getVersion() ?: 'unknown';
        }

        $this->initStatusWriter();
    }

    /**
     * Checks if xdebug is loaded and composer needs to be restarted
     *
     * If so, then a tmp ini is created with the xdebug ini entry commented out.
     * If scanned inis have been loaded, these are combined into the tmp ini
     * and PHP_INI_SCAN_DIR is set to an empty value. Current ini locations are
     * are stored in MYAPP_ORIGINAL_INIS (where 'MYAPP' is the prefix passed in the
     * constructor) for use in the restarted process.
     *
     * This behaviour can be disabled by setting the MYAPP_ALLOW_XDEBUG
     * environment variable to 1. This variable is used internally so that the
     * restarted process is created only once and PHP_INI_SCAN_DIR can be
     * restored to its original value.
     */
    public function check()
    {
        if (!$this->cli) {
            return;
        }

        $envArgs = explode('|', strval(getenv($this->envAllowXdebug)), 4);

        if ($this->loaded && empty($envArgs[0])) {
            // Restart required
            $this->write(Status::RESTART);

            if ($this->prepareRestart()) {
                $command = $this->getCommand($_SERVER['argv']);
                $this->restart($command);
            }
            return;
        }

        if (self::RESTART_ID === $envArgs[0] && count($envArgs) >= 3) {
            // Restarting, so unset environment variable and extract saved values
            $this->write(Status::RESTARTED);

            putenv($this->envAllowXdebug);
            $version = $envArgs[1];
            $scannedInis = $envArgs[2];

            if (!$this->loaded) {
                // Version is only set if restart is successful
                self::$skipped = $version;
            }

            if ($scannedInis) {
                // Scan dir will have been changed, so restore it
                if (isset($envArgs[3])) {
                    putenv('PHP_INI_SCAN_DIR='.$envArgs[3]);
                } else {
                    putenv('PHP_INI_SCAN_DIR');
                }
            }
            return;
        }

        $this->write(Status::NORESTART);
    }

    /**
     * Returns an array of php.ini locations with at least one entry
     *
     * The equivalent of calling php_ini_loaded_file then php_ini_scanned_files.
     * The loaded ini location is the first entry and may be empty.
     *
     * @return array
     */
    public static function getAllIniFiles()
    {
        if (!empty(self::$name)) {
            $env = getenv(self::$name.self::SUFFIX_INIS);

            if (false !== $env) {
                return explode(PATH_SEPARATOR, $env);
            }
        }

        $paths = array(strval(php_ini_loaded_file()));

        if ($scanned = php_ini_scanned_files()) {
            $paths = array_merge($paths, array_map('trim', explode(',', $scanned)));
        }

        return $paths;
    }

    /**
     * Returns the xdebug version that triggered a successful restart
     *
     * @return string
     */
    public static function getSkippedVersion()
    {
        return strval(self::$skipped);
    }

    /**
     * Executes the restarted command then deletes the tmp ini
     *
     * @param string $command
     */
    protected function restart($command)
    {
        $this->write(Status::RESTARTING);
        passthru($command, $exitCode);

        if (!empty($this->tmpIni)) {
            @unlink($this->tmpIni);
        }

        exit($exitCode);
    }

    /**
     * Returns true if everything was written for the restart
     *
     * If any of the following fails (however unlikely) we must return false to
     * stop potential recursion:
     *   - tmp ini file creation
     *   - environment variable creation
     *
     * @return bool
     */
    private function prepareRestart()
    {
        $error = '';
        $iniFiles = self::getAllIniFiles();
        $scannedInis = count($iniFiles) > 1;
        $scanDir = getenv('PHP_INI_SCAN_DIR');

        if (!defined('PHP_BINARY')) {
            $error = 'PHP version is too old: '.PHP_VERSION;
        } elseif (!$this->writeTmpIni($iniFiles)) {
            $error = 'Unable to create tmp ini file';
        } elseif (!$this->setEnvironment($scannedInis, $scanDir, $iniFiles)) {
            $error = 'Unable to set environment variables';
        }

        if ($error) {
            $this->write(Status::ERROR, $error);
        }

        return empty($error);
    }

    /**
     * Returns true if the tmp ini file was written
     *
     * The filename is passed as the -c option when the process restarts.
     *
     * @param array $iniFiles All ini files used in the current process
     *
     * @return bool
     */
    private function writeTmpIni(array $iniFiles)
    {
        if (!$this->tmpIni = tempnam(sys_get_temp_dir(), '')) {
            return false;
        }

        // $iniFiles has at least one item and it may be empty
        if (empty($iniFiles[0])) {
            array_shift($iniFiles);
        }

        $content = '';
        $regex = '/^\s*(zend_extension\s*=.*xdebug.*)$/mi';

        foreach ($iniFiles as $file) {
            $data = preg_replace($regex, ';$1', file_get_contents($file));
            $content .= $data.PHP_EOL;
        }

        $content .= 'allow_url_fopen='.ini_get('allow_url_fopen').PHP_EOL;
        $content .= 'disable_functions="'.ini_get('disable_functions').'"'.PHP_EOL;
        $content .= 'memory_limit='.ini_get('memory_limit').PHP_EOL;

        if (defined('PHP_WINDOWS_VERSION_BUILD')) {
            // Work-around for PHP windows bug, see issue #6052
            $content .= 'opcache.enable_cli=0'.PHP_EOL;
        }

        return @file_put_contents($this->tmpIni, $content);
    }

    /**
     * Returns the restart command line
     *
     * @param array $args The argv array
     *
     * @return string
     */
    private function getCommand(array $args)
    {
        if (Process::supportsColor(STDOUT)) {
            $args = Process::addColorOption($args, $this->colorOption);
        }

        $args = array_merge(array(PHP_BINARY, '-c', $this->tmpIni), $args);

        $cmd = Process::escape(array_shift($args), true, true);
        foreach ($args as $arg) {
            $cmd .= ' '.Process::escape($arg);
        }

        return $cmd;
    }

    /**
     * Returns true if the restart environment variables were set
     *
     * @param bool  $scannedInis Whether there were scanned ini files
     * @param false|string $scanDir PHP_INI_SCAN_DIR environment variable
     * @param array $iniFiles All ini files used in the current process
     *
     * @return bool
     */
    private function setEnvironment($scannedInis, $scanDir, array $iniFiles)
    {
        // Set scan dir env to an empty value if there were scanned ini files
        if ($scannedInis && !putenv('PHP_INI_SCAN_DIR=')) {
            return false;
        }

        // Make original inis available to restarted process
        if (!putenv($this->envOriginalInis.'='.implode(PATH_SEPARATOR, $iniFiles))) {
            return false;
        }

        // Flag restarted process and save values for it to use
        $envArgs = array(
            self::RESTART_ID,
            $this->loaded,
            intval($scannedInis),
        );

        if ($scannedInis && false !== $scanDir) {
            // Only add original scan dir if it was set
            $envArgs[] = $scanDir;
        }

        return putenv($this->envAllowXdebug.'='.implode('|', $envArgs));
    }

    /**
     * Creates a Status instance if one is required
     *
     */
    private function initStatusWriter()
    {
        if (!$this->cli || !in_array('-vvv', $_SERVER['argv'])) {
            return;
        }

        $start = getenv(Status::ENV_RESTART);
        $time = $start ? round((microtime(true) - $start) * 1000) : 0;
        putenv(Status::ENV_RESTART);

        $this->writer = new Status($this->loaded, $this->envAllowXdebug, $time);
    }

    /**
     * Prints verbose status messages
     *
     * @param string $op Status handler constant
     * @param null|string $data Optional data
     */
    private function write($op, $data = null)
    {
        if ($this->writer) {
            $this->writer->report($op, $data);
        }
    }
}
