# composer/xdebug-handler

Restart a CLI process without loading the Xdebug extension.

Originally written as part of [composer/composer](https://github.com/composer/composer),
now extracted and made available as a stand-alone library.

[![Build Status](https://travis-ci.org/composer/xdebug-handler.svg?branch=master)](https://travis-ci.org/composer/xdebug-handler)
[![Build status](https://ci.appveyor.com/api/projects/status/token?svg=true)](https://ci.appveyor.com/project/Seldaek/xdebug-handler)

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

- `MYAPP_ALLOW_XDEBUG=1` to skip the restart if xdebug is loaded
- `MYAPP_ORIGINAL_INIS` to obtain ini file locations in a restarted process

#### _$colorOption_
This optional value is added to the restart command-line and is needed to force color output in a piped child process. Only long-options are supported, for example `--ansi` `--colors=always` etc.

If the original command-line contains an argument that pattern-matches this value, for example `--no-ansi` `--colors=never`, then _$colorOption_ is ignored.

Do not use this parameter if the input handler cannot cope with an option as the last argument.

## Advanced Usage
If the application does anything with ini files, then functions like `php_ini_loaded_file` and `php_ini_scanned_files` will not work correctly in a restarted process.

To make the original locations available, they are saved to the environment variable suffixed `_ORIGINAL_INIS`. This is a path-separated string comprising the location returned from `php_ini_loaded_file`, which could be empty, followed by locations parsed from calling `php_ini_scanned_files`.

A static helper function `XdebugHandler::getAllIniFiles` is provided to access these values in a single array, regardless of whether the process has been restarted or not.

```php
use Composer\XdebugHandler\XdebugHandler;

$files = XdebugHandler::getAllIniFiles();

// $files[0] always exists, it could be an empty string
$loadedIni = array_shift($files);
$scannedInis = $files;
```

## License
composer/xdebug-handler is licensed under the MIT License, see the LICENSE file for details.
