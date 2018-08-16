# composer/xdebug-handler

[![packagist](https://img.shields.io/packagist/v/composer/xdebug-handler.svg)](https://packagist.org/packages/composer/xdebug-handler)
[![linux build](https://img.shields.io/travis/composer/xdebug-handler/master.svg?label=linux+build)](https://travis-ci.org/composer/xdebug-handler)
[![windows build](https://img.shields.io/appveyor/ci/Seldaek/xdebug-handler/master.svg?label=windows+build)](https://ci.appveyor.com/project/Seldaek/xdebug-handler)
![license](https://img.shields.io/github/license/composer/xdebug-handler.svg)
![php](https://img.shields.io/packagist/php-v/composer/xdebug-handler.svg?colorB=8892BF&label=php)

Restart a CLI process without loading the xdebug extension.

Originally written as part of [composer/composer](https://github.com/composer/composer),
now extracted and made available as a stand-alone library.

## Installation

Install the latest version with:

```bash
$ composer require composer/xdebug-handler
```

## Requirements

* PHP 5.3.2 minimum, although functionality is disabled below PHP 5.4.0. Using the latest PHP version is highly recommended.

## Basic Usage
```php
use Composer\XdebugHandler\XdebugHandler;

$xdebug = new XdebugHandler('myapp');
$xdebug->check();
unset($xdebug);
```

The constructor takes two parameters:

#### _$envPrefix_
This is used to create distinct environment variables and is upper-cased and prepended to default base values. The above example enables the use of:

- `MYAPP_ALLOW_XDEBUG=1` to override automatic restart and allow xdebug
- `MYAPP_ORIGINAL_INIS` to obtain ini file locations in a restarted process

#### _$colorOption_
This optional value is added to the restart command-line and is needed to force color output in a piped child process. Only long-options are supported, for example `--ansi` `--colors=always` etc.

If the original command-line contains an argument that pattern-matches this value, for example `--no-ansi` `--colors=never`, then _$colorOption_ is ignored.

If the pattern-match ends with =auto, for example `--colors=auto`, the argument is replaced by _$colorOption_. Otherwise it is added at either the end of the command-line, or preceeding a double-dash `--` delimiter.

## Advanced Usage
### How it works

A temporary ini file is created from the loaded (and scanned) ini files, with any references to the xdebug extension commented out. Current ini settings are merged, so that settings made on the command-line or by the application are included.

* `MYAPP_ALLOW_XDEBUG` is set with internal data to flag and use in the restart.
* The `-n` option is added to the command-line. This tells PHP not to scan for additional inis.
* The temporary ini is added to the command-line with the `-c` option.
* The application is restarted in a new process using `passthru`.
    * `MYAPP_ALLOW_XDEBUG` is unset.
    * The application runs and exits.
* The main process exits with the exit code from the restarted process.

### Ini files
If the application does anything with ini files, then functions like `php_ini_loaded_file` and `php_ini_scanned_files` will not work correctly in a restarted process.

To make the original locations available, they are saved to the environment variable suffixed `_ORIGINAL_INIS`. This is a path-separated string comprising the location returned from `php_ini_loaded_file`, which could be empty, followed by locations parsed from calling `php_ini_scanned_files`.

A static helper method `XdebugHandler::getAllIniFiles` is provided to access these values in a single array, regardless of whether the process has been restarted or not.

```php
use Composer\XdebugHandler\XdebugHandler;

$files = XdebugHandler::getAllIniFiles();

# $files[0] always exists, it could be an empty string
$loadedIni = array_shift($files);
$scannedInis = $files;
```

### Restarted process
Other static helper methods provide information about the current process, which may or may not have been restarted:

* `XdebugHandler::getSkippedVersion` - the xdebug version string that was skipped by the restart, or an empty value.
* `XdebugHandler::getRestartSettings` - an array of settings to use with PHP sub-processes, or null.

```php
use Composer\XdebugHandler\XdebugHandler;

$version = XdebugHandler::getSkippedVersion();
# $version: '2.6.0' (for example), or an empty string

$settings = XdebugHandler::getRestartSettings();
/**
 * $settings: array (if the current process was restarted,
 * or called with the settings from a previous restart), or null
 *
 *    'tmpIni'      => the temporary ini file used in the restart (string)
 *    'scannedInis' => if there were any scanned inis (bool)
 *    'scanDir'     => the original PHP_INI_SCAN_DIR value (false|string)
 *    'phprc'       => the original PHPRC value (false|string)
 *    'inis'        => the original inis from getAllIniFiles (array)
 *    'skipped'     => the skipped version from getSkippedVersion (string)
 */
```

#### Sub-processes
Calling a PHP process from a restarted process using the **original** configuration will result in one of two outcomes:

1. xdebug will be loaded in the new process.
2. If the new process implements xdebug-handler, it will restart without loading xdebug.

The `XdebugHandler::getRestartSettings()` method provides data that can be used to call a PHP sub-process. For example:

*   Using **standard** restart settings.
    * Add `-n`, `-c`, `tmpIni` to the command-line.
    * xdebug will not be loaded, but points 1 and 2 apply to any sub-process.

*   Using **persistent** environment variables.
    * Set `PHPRC` to `tmpIni`
    * Set `PHP_INI_SCAN_DIR` to an empty string if `scannedInis` is true.
    * xdebug will not be loaded in this or any sub-process.

The PhpConfig class can be used to manage these scenarios.

#### PhpConfig
This helper class provides command-line options and sets up the environment for calling a PHP sub-process. If there was no restart (because it was overriden, or xdebug is not present), an empty options array is returned and the environment is not changed.

```php
use Composer\XdebugHandler\PhpConfig;

$config = new PhpConfig;

$options = $config->useOriginal();
# $options:     empty array
# environment:  restored

$options = $config->useStandard();
# $options:     [-n, -c, tmpIni]
# environment:  restored

$options = $config->usePersistent();
# $options:    empty array
# environment: sets PHPRC, PHP_INI_SCAN_DIR (if needed)
```

### Output
The `setLogger` method enables the output of status messages to an external PSR3 logger.

```php
use Composer\XdebugHandler\XdebugHandler;

$xdebug = new XdebugHandler('myapp');
// Provide a PSR3 logger
$xdebug->setLogger($myLogger);
```

All messages are reported with either `DEBUG` or `WARNING` log levels. For example (showing the level and message):

```
// Restart overridden
DEBUG    Checking MYAPP_ALLOW_XDEBUG
DEBUG    The xdebug extension is loaded (2.5.0)
DEBUG    No restart (MYAPP_ALLOW_XDEBUG=1)

// Failed restart
DEBUG    Checking MYAPP_ALLOW_XDEBUG
DEBUG    The xdebug extension is loaded (2.5.0)
WARNING  No restart (Unable to create temporary ini file)
```

Status messages can also be output with `XDEBUG_HANDLER_DEBUG`. See the Debugging section, below.

### Main script
The process will not be restarted if the `argv` location of the main script is inaccessible. This is only likely in more esoteric use-cases and can be fixed by using the `setMainScript` method.

```php
// Save the full path to the invoked script
$mainScript = realpath($_SERVER['argv'][0]);
...

use Composer\XdebugHandler\XdebugHandler;

$xdebug = new XdebugHandler('myapp');
$xdebug->setMainScript($mainScript);

// The script name "--" is also supported (for standard input)
```

### Extending the library
The API is defined by classes and their accessible elements that are not annotated as @internal. The main class has two protected methods that can be overriden to provide additional functionality:

#### _requiresRestart($isLoaded)_
By default the process will restart if xdebug is loaded. Overriding this allows an application to decide. This method is only called if `MYAPP_ALLOW_XDEBUG` is empty.

#### _restart($command)_
An application can hook into this to access the temporary ini file, its location given in the `tmpIni` property.

### Debugging
The following environment settings can be used to troubleshoot unexpected behavior:

* `XDEBUG_HANDLER_DEBUG=1` Outputs status messages to standard output, irrespective of any PSR3 logger. Each message is prefixed `xdebug-handler[pid]`, where pid is the process identifier.

* `XDEBUG_HANDLER_DEBUG=2` As above, but additionally saves the temporary ini file and reports its location in a status message.

## License
composer/xdebug-handler is licensed under the MIT License, see the LICENSE file for details.
