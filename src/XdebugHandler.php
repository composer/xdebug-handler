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

    private $colorOption;
    private $loaded;
    private $envAllowXdebug;
    private $envOriginalInis;
    private $envScanDir;
    private $version;
    private $tmpIni;

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
        $this->loaded = extension_loaded('xdebug');
        $this->envScanDir = getenv('PHP_INI_SCAN_DIR');

        if ($this->loaded) {
            $ext = new \ReflectionExtension('xdebug');
            $this->version = $ext->getVersion() ?: 'unknown';
        }
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
        $envArgs = explode('|', strval(getenv($this->envAllowXdebug)), 4);

        if ($this->needsRestart($envArgs[0])) {
            if ($this->prepareRestart()) {
                $command = $this->getCommand($_SERVER['argv']);
                $this->restart($command);
            }

            return;
        }

        if (self::RESTART_ID === $envArgs[0] && count($envArgs) >= 3) {
            // Restarting, so unset environment variable and extract saved values
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
        $iniFiles = self::getAllIniFiles();
        $scannedInis = count($iniFiles) > 1;

        if ($this->writeTmpIni($iniFiles)) {
            return $this->setEnvironment($scannedInis, $iniFiles);
        }

        return false;
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
        if ($this->outputSupportsColor(STDOUT)) {
            $args = $this->addColorOption($args, $this->colorOption);
        }

        $args = array_merge(array(PHP_BINARY, '-c', $this->tmpIni), $args);

        $cmd = $this->escape(array_shift($args), true, true);
        foreach ($args as $arg) {
            $cmd .= ' '.$this->escape($arg);
        }

        return $cmd;
    }

    /**
     * Returns true if the restart environment variables were set
     *
     * @param bool  $scannedInis Whether there were scanned ini files
     * @param array $iniFiles All ini files used in the current process
     *
     * @return bool
     */
    private function setEnvironment($scannedInis, array $iniFiles)
    {
        // Set scan dir to an empty value if there were any scanned ini files
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
            $this->version,
            intval($scannedInis),
        );

        if ($scannedInis && false !== $this->envScanDir) {
            // Only add original scan dir if it was set
            $envArgs[] = $this->envScanDir;
        }

        return putenv($this->envAllowXdebug.'='.implode('|', $envArgs));
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
     * @param bool $module The argument is the module to invoke
     *
     * @return string The escaped argument
     */
    private function escape($arg, $meta = true, $module = false)
    {
        if (!defined('PHP_WINDOWS_VERSION_BUILD')) {
            return escapeshellarg($arg);
        }

        $quote = strpbrk($arg, " \t") !== false || $arg === '';
        $arg = preg_replace('/(\\\\*)"/', '$1$1\\"', $arg, -1, $dquotes);

        if ($meta) {
            $meta = $dquotes || preg_match('/%[^%]+%/', $arg);

            if (!$meta) {
                $quote = $quote || strpbrk($arg, '^&|<>()') !== false;
            } elseif ($module && !$dquotes && $quote) {
                $meta = false;
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
     * This is tricky on Windows, because Cygwin, Msys2 etc emulate pseudo
     * terminals via named pipes, so we can only check the environment.
     *
     * @param mixed $output A valid output stream
     *
     * @return bool
     */
    private function outputSupportsColor($output)
    {
        if (defined('PHP_WINDOWS_VERSION_BUILD')) {
            // Switch on vt100 support if we can
            if (function_exists('sapi_windows_vt100_support')
                && sapi_windows_vt100_support($output, true)) {
                return true;
            }

            return (false !== getenv('ANSICON')
                || 'ON' === getenv('ConEmuANSI')
                || 'xterm' === getenv('TERM'));
        }

        if (function_exists('stream_isatty')) {
            return stream_isatty($output);
        } elseif (function_exists('posix_isatty')) {
            return posix_isatty($output);
        }

        $stat = fstat($output);
        // Check if formatted mode is S_IFCHR
        return $stat ? 0020000 === ($stat['mode'] & 0170000) : false;
    }
}
