# Functional Test Scripts

This folder contains the PHP scripts that produce the output for the functional tests.

They can also be run from the command-line to provide a visual representation of xdebug-handler's
inner workings.

## Usage

There are two core scripts, `app-basic` which includes xdebug-handler, and `app-plain` which
does not.

```sh
cd tests/App

php app-basic.php --display
php app-plain.php --display
```

The `--display` options provides colorized output with a detailed list of xdebug-handler values:

* [active] - from isXdebugActive()
* [skipped] - from getSkippedVersion()
* [env] - environment variables (PHPRC and PHP_INI_SCAN_DIR)
* [ini] - from getAllIniFiles(). Use the `--inis` options to show them all.
* [settings] - from getRestartSettings()

These values are obtained after any restart has happened.

Each line of output is prefixed `name[pid]`, where name is either 'logger' (for xdebug-handler's
internal logger) or the name of the script, and pid is the process identifier.

## Scripts

The following scripts are in addition to the core scripts (which are used by some of these).
### app-extend-allow

Demonstrates how an extended class can decide whether to restart or not.

```sh
cd tests/App

# restarts if no help command or option
php app-extend-allow.php --display

# no restart with -h, --help or help
php app-extend-allow.php -h --display
```

### app-extend-ini

Demonstrates how an extended class can restart with a specific ini setting, even if xdebug is not
loaded. The `phar_readonly` ini setting is used in this example.

```sh
cd tests/App

php -dphar.read_only=1 app-extend-ini.php --display
```

### app-extend-mode

Demonstrates how xdebug can be restarted in a different mode, in this example `xdebug.mode=coverage`.

```sh
cd tests/App

php -dxdebug.mode=develop app-extend-mode.php --display
```

### app-persistent

Demonstrates the use of persistent settings (to remove xdebug from all sub-processes) and PhpConfig
to enable xdebug when required. The following sub-processes are called:

 * app-plain.php (xdebug not loaded)
 * app-plain.php with original settings (xdebug will be loaded)
 * app-other.php (xdebug not loaded)
```sh
php app-persistent.php --display
```

### app-prepend

Demonstrates the use of an auto prepend file to remove xdebug from a script. Note that either
`app-basic` or `app-plain` can be used here.

```sh
cd tests/App

php -dauto_prepend_file=app-prepend.php app-plain.php --display
```

The `auto_prepend_file` ini setting from the command-line is picked up when the ini content is
merged, so it is available in the restart.

If stdin is used, it will be read in the restart:

```sh
# Unixy
cat app-plain.php | php -dauto_prepend_file=app-prepend.php -- --display

# Windows
type app-plain.php | php -dauto_prepend_file=app-prepend.php -- --display
```

### app-stdin

Included here for demonstration purposes only.

Uses xdebug-handler from stdin to remove xdebug from a script. Note that either `app-basic` or
`app-plain` can be used here.

```sh
cd tests/App

# Unixy
cat app-stdin.php | php -- app-plain.php --display

# Windows
type app-stdin.php | php -- app-plain.php --display
```

While this works in simple scenarios, it is not recommended because the restart settings are not
available. This is due to a different script restarting, thus preventing `app-stdin` from setting
the restart environment variable.

Additionally _getAllIniFiles()_ returns the temp ini used in the
restart, because the restarting script is not looking for its own named environment variable.
