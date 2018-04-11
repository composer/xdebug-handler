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

Do not use this parameter if the input handler cannot cope with an option as the last argument.

## Advanced Usage
### How it works

A temporary ini file is created from the loaded (and scanned) ini files, with any references to the xdebug extension commented out. Current ini settings are merged, so that settings made on the command-line or by the application are included.

* `MYAPP_ALLOW_XDEBUG` is set with internal data to flag and use in the restart.
* If any scanned ini files were used, `PHP_INI_SCAN_DIR` is set to an empty string. This tells PHP not to scan for additional inis.
* The temporary ini is added to the command-line with the `-c` option.
* The application is restarted in a new process using `passthru`.
    * `MYAPP_ALLOW_XDEBUG` is unset.
    * `PHP_INI_SCAN_DIR` is restored to its original value (if it was changed).
    * The application runs and exits.
* The main process exits with the exit code from the restarted process.

### Ini files
If the application does anything with ini files, then functions like `php_ini_loaded_file` and `php_ini_scanned_files` will not work correctly in a restarted process.

To make the original locations available, they are saved to the environment variable suffixed `_ORIGINAL_INIS`. This is a path-separated string comprising the location returned from `php_ini_loaded_file`, which could be empty, followed by locations parsed from calling `php_ini_scanned_files`.

A static helper method `XdebugHandler::getAllIniFiles` is provided to access these values in a single array, regardless of whether the process has been restarted or not.

```php
use Composer\XdebugHandler\XdebugHandler;

$files = XdebugHandler::getAllIniFiles();

// $files[0] always exists, it could be an empty string
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
// $version: '2.6.0' (for example), or an empty string

$settings = XdebugHandler::getRestartSettings();
/**
 * $settings: array (if the current process was restarted,
 * or called with the settings from a previous restart), or null
 *
 *    'tmpIni'      => the temporary ini file used in the restart (string)
 *    'scannedInis' => if there were any scanned inis (bool)
 *    'scanDir'     => the original PHP_INI_SCAN_DIR value (false|string)
  *   'inis'        => the original inis from getAllIniFiles (array)
 *    'skipped'     => the skipped version from getSkippedVersion (string)
 */
```

#### Sub-processes
Calling a PHP process from a restarted process will result in xdebug being loaded in that process, or another restart if xdebug-handler is implemented.

The `XdebugHandler::getRestartSettings()` method is provided so that an application can call a PHP process with the same settings that were used in a restart:

* If `scannedInis` is true, set `PHP_INI_SCAN_DIR` to an empty string.
* Add `tmpIni`to the command-line with the `-c` option.
* Run the process.
    * If xdebug-handler is implemented, its internal settings are synced and `PHP_INI_SCAN_DIR` is restored to its original value (if it was changed).
* If `PHP_INI_SCAN_DIR` was changed, restore it using `scanDir`.

This solution is not without its pitfalls. In addition to the ini files issue outlined above, `PHP_INI_SCAN_DIR` is not restored in the sub-process (unless xdebug-hander is implemented). This will cause problems if it was changed and the sub-process calls another PHP process.

However, an application can safely spawn itself, or other scripts that it controls, or other applications that implement xdebug-handler.

### Output
The `setLogger` method enables the output of status messages to an external PSR3 logger.

```php
use Composer\XdebugHandler\XdebugHandler;

$xdebug = new XdebugHandler('myapp');
// Provide a PSR3 logger
$xdebug->setLogger($myLogger);
```

All messages are reported with either `DEBUG` or `WARNING` log levels. For example:

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

### Main script
The process will not be restarted if the location of the main script is inaccessible. This may occur if the working directory has been changed and can be fixed by using the `setMainScript` method.

```php
// Save the full path to the invoked script
$mainScript = realpath($_SERVER['argv'][0]);
...

use Composer\XdebugHandler\XdebugHandler;

$xdebug = new XdebugHandler('myapp');
$xdebug->setMainScript($mainScript);
```

### Extending the library
The API is defined by classes and their accessible elements that are not annotated as @internal. The main class has two protected methods that can be overriden to provide additional functionality:

#### _requiresRestart($isLoaded)_
By default the process will restart if xdebug is loaded. Overriding this allows an application to decide. This method is only called if `MYAPP_ALLOW_XDEBUG` is empty.

#### _restart($command)_
An application can hook into this to access the temporary ini file, its location given in the `tmpIni` property.

## License
composer/xdebug-handler is licensed under the MIT License, see the LICENSE file for details.
