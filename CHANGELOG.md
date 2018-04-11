## [Unreleased]

## [1.1.0] - 2018-04-11
  * Added: `getRestartSettings` method for calling PHP processes in a restart process.
  * Added: API definition and @internal class annotations.
  * Added: protected `requiresRestart` method for extending classes.
  * Added: `setMainScript` method for applications that change the working directory.
  * Changed: private `tmpIni` variable to protected for extending classes.
  * Fixed: environment variables not available in $_SERVER when restored in the restart.
  * Fixed: relative path problems caused by Phar::interceptFileFuncs - [composer/xdebug-handler#46](https://github.com/composer/xdebug-handler/issues/46).
  * Fixed: incorrect handling when script file cannot be found.

## [1.0.0] - 2018-03-08
  * Added: PSR3 logging for optional status output.
  * Added: existing ini settings are merged to catch command-line overrides.
  * Added: code, tests and other artefacts to decouple from Composer.
  * Break: the following class was renamed:
    - `Composer\XdebugHandler` -> `Composer\XdebugHandler\XdebugHandler`

[Unreleased]: https://github.com/composer/xdebug-handler/compare/1.1.0...HEAD
[1.1.0]: https://github.com/composer/xdebug-handler/compare/1.0.0...1.1.0
[1.0.0]: https://github.com/composer/xdebug-handler/compare/d66f0d15cb57...1.0.0
