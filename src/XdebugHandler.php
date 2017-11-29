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
    const ENV_ALLOW = 'COMPOSER_ALLOW_XDEBUG';
    const ENV_ORIGINAL = 'COMPOSER_ORIGINAL_INIS';
    const RESTART_ID = 'internal';

    private static $skipped;
    private $colorOption;
    private $loaded;
    private $envScanDir;
    private $version;
    private $tmpIni;

    /**
     * Constructor
     *
     * @param $colorOption string Command-line long option to force color output
     */
    public function __construct($colorOption = '')
    {
        $this->colorOption = $colorOption;
        $this->loaded = extension_loaded('xdebug');
        $this->envScanDir = getenv('PHP_INI_SCAN_DIR');

        if ($this->loaded) {
            $ext = new \ReflectionExtension('xdebug');
            $this->version = strval($ext->getVersion());
        }
    }

    /**
     * Checks if xdebug is loaded and composer needs to be restarted
     *
     * If so, then a tmp ini is created with the xdebug ini entry commented out.
     * If additional inis have been loaded, these are combined into the tmp ini
     * and PHP_INI_SCAN_DIR is set to an empty value. Current ini locations are
     * are stored in COMPOSER_ORIGINAL_INIS, for use in the restarted process.
     *
     * This behaviour can be disabled by setting the COMPOSER_ALLOW_XDEBUG
     * environment variable to 1. This variable is used internally so that the
     * restarted process is created only once and PHP_INI_SCAN_DIR can be
     * restored to its original value.
     */
    public function check()
    {
        $args = explode('|', strval(getenv(self::ENV_ALLOW)), 3);

        if ($this->needsRestart($args[0])) {
            if ($this->prepareRestart()) {
                $command = $this->getCommand($_SERVER['argv']);
                $this->restart($command);
            }

            return;
        }

        // Restore environment variables if we are restarting
        if (self::RESTART_ID === $args[0]) {
            putenv(self::ENV_ALLOW);

            if (false !== $this->envScanDir) {
                // $args[1] contains the original value
                if (isset($args[1])) {
                    putenv('PHP_INI_SCAN_DIR='.$args[1]);
                } else {
                    putenv('PHP_INI_SCAN_DIR');
                }
            }

            // $args[2] contains the xdebug version
            if (isset($args[2]) && !$this->loaded) {
                self::$skipped = $args[2];
            }
        }
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
        $env = getenv(self::ENV_ORIGINAL);

        if (false !== $env) {
            return explode(PATH_SEPARATOR, $env);
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
        passthru($command, $exitCode);

        if (!empty($this->tmpIni)) {
            @unlink($this->tmpIni);
        }

        exit($exitCode);
    }

    /**
     * Returns true if a restart is needed
     *
     * @param string $allow Environment value
     *
     * @return bool
     */
    private function needsRestart($allow)
    {
        if (PHP_SAPI !== 'cli' || !defined('PHP_BINARY')) {
            return false;
        }

        return empty($allow) && $this->loaded;
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
        $this->tmpIni = '';
        $iniPaths = self::getAllIniFiles();
        $additional = count($iniPaths) > 1;

        if ($this->writeTmpIni($iniPaths)) {
            return $this->setEnvironment($additional, $iniPaths);
        }

        return false;
    }

    /**
     * Returns true if the tmp ini file was written
     *
     * The filename is passed as the -c option when the process restarts.
     *
     * @param array $iniPaths Locations reported by the current process
     *
     * @return bool
     */
    private function writeTmpIni(array $iniPaths)
    {
        if (!$this->tmpIni = tempnam(sys_get_temp_dir(), '')) {
            return false;
        }

        // $iniPaths has at least one item and it may be empty
        if (empty($iniPaths[0])) {
            array_shift($iniPaths);
        }

        $content = '';
        $regex = '/^\s*(zend_extension\s*=.*xdebug.*)$/mi';

        foreach ($iniPaths as $file) {
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
        $phpArgs = array(PHP_BINARY, '-c', $this->tmpIni);

        if ($this->hasColorOutput(STDOUT)) {
            $args = $this->addColorOption($args, $this->colorOption);
        }

        return implode(' ', array_map(array($this, 'escape'), array_merge($phpArgs, $args)));
    }

    /**
     * Returns true if the restart environment variables were set
     *
     * @param bool  $additional Whether there were additional inis
     * @param array $iniPaths Locations reported by the current process
     *
     * @return bool
     */
    private function setEnvironment($additional, array $iniPaths)
    {
        // Set scan dir to an empty value if additional ini files were used
        if ($additional && !putenv('PHP_INI_SCAN_DIR=')) {
            return false;
        }

        // Make original inis available to restarted process
        if (!putenv(self::ENV_ORIGINAL.'='.implode(PATH_SEPARATOR, $iniPaths))) {
            return false;
        }

        // Flag restarted process and save scan dir and version
        $args = array(
            self::RESTART_ID,
            strval($this->envScanDir),
            $this->version,
        );

        return putenv(self::ENV_ALLOW.'='.implode('|', $args));
    }

    /**
     * Returns the restart arguments, appending a color option if required
     *
     * We are running in a terminal with color support, but the restarted
     * process cannot know this because its output is piped. Providing a color
     * option signifies that color output is supported.
     *
     * @param array $args The argv array
     * @param $colorOption The long option to force color output
     *
     * @return array
     */
    private function addColorOption(array $args, $colorOption)
    {
        if (in_array($colorOption, $args)
            || !preg_match('/^--([a-z]+$)|(^--[a-z]+=)/', $colorOption, $matches)) {
            return $args;
        }

        if (isset($matches[2])) {
            // Handle --color(s)= options. Note args[0] is the script name
            if ($index = array_search($matches[2].'auto', $args)) {
                $args[$index] = $colorOption;
                return $args;
            } elseif (preg_grep('/^'.$matches[2].'/', $args)) {
                return $args;
            }
        } elseif (in_array('--no-'.$matches[1], $args)) {
            return $args;
        }

        $args[] = $colorOption;
        return $args;
    }

    /**
     * Escapes a string to be used as a shell argument.
     *
     * From https://github.com/johnstevenson/winbox-args
     * MIT Licensed (c) John Stevenson <john-stevenson@blueyonder.co.uk>
     *
     * @param string $arg  The argument to be escaped
     * @param bool   $meta Additionally escape cmd.exe meta characters
     *
     * @return string The escaped argument
     */
    private function escape($arg, $meta = true)
    {
        if (!defined('PHP_WINDOWS_VERSION_BUILD')) {
            return escapeshellarg($arg);
        }

        $quote = strpbrk($arg, " \t") !== false || $arg === '';
        $arg = preg_replace('/(\\\\*)"/', '$1$1\\"', $arg, -1, $dquotes);

        if ($meta) {
            $meta = $dquotes || preg_match('/%[^%]+%/', $arg);

            if (!$meta && !$quote) {
                $quote = strpbrk($arg, '^&|<>()') !== false;
            }
        }

        if ($quote) {
            $arg = preg_replace('/(\\\\*)$/', '$1$1', $arg);
            $arg = '"'.$arg.'"';
        }

        if ($meta) {
            $arg = preg_replace('/(["^&|<>()%])/', '^$1', $arg);
        }

        return $arg;
    }

    /**
     * Returns true if the output stream supports colors
     *
     * @param mixed $output A valid output stream
     *
     * @return bool
     */
    private function hasColorOutput($output)
    {
        if (defined('PHP_WINDOWS_VERSION_BUILD')) {
            // Switch on vt100 support if we can
            if (function_exists('sapi_windows_vt100_output')) {
                return sapi_windows_vt100_support($output, true);
            }

            $stat = fstat($output);
            // Check if formatted mode is S_IFCHR
            $isatty = $stat ? 0020000 === ($stat['mode'] & 0170000) : false;

            return $isatty
                && (false !== getenv('ANSICON')
                || 'ON' === getenv('ConEmuANSI')
                || 'xterm' === getenv('TERM')
                || 'cygwin' === getenv('TERM'));
        }

        return function_exists('posix_isatty') && posix_isatty($output);
    }
}
