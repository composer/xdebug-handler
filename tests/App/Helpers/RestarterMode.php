<?php

declare(strict_types=1);

/*
 * This file is part of composer/xdebug-handler.
 *
 * (c) Composer <https://github.com/composer>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Composer\XdebugHandler\Tests\App\Helpers;

use Composer\Pcre\Preg;
use Composer\XdebugHandler\XdebugHandler;
use Composer\XdebugHandler\Process;

/**
 * Restarts with xdebug loaded in coverage mode, by uncommenting xdebug in the
 * temporary ini file and setting the XDEBUG_MODE environment variable.
 *
 */

class RestarterMode extends XdebugHandler
{
    protected function requiresRestart(bool $default): bool
    {
        if ($default) {
            $version = (string) phpversion('xdebug');

            if (version_compare($version, '3.1', '>=')) {
                $modes = xdebug_info('mode');
                return !in_array('coverage', $modes, true);
            }

            // See if xdebug.mode is supported in this version
            $iniMode = ini_get('xdebug.mode');
            if ($iniMode === false) {
                return false;
            }

            // Environment value wins but cannot be empty
            $envMode = (string) getenv('XDEBUG_MODE');
            if ($envMode !== '') {
                $mode = $envMode;
            } else {
                $mode = $iniMode !== '' ? $iniMode : 'off';
            }

            // An empty comma-separated list is treated as mode 'off'
            if (Preg::isMatch('/^,+$/', str_replace(' ', '', $mode))) {
                $mode = 'off';
            }

            $modes = explode(',', str_replace(' ', '', $mode));
            return !in_array('coverage', $modes, true);
        }

        return false;
    }

    protected function restart(array $command): void
    {
        // uncomment last xdebug line
        $regex = '/^;\s*(zend_extension\s*=.*xdebug.*)$/mi';
        $content = (string) file_get_contents((string) $this->tmpIni);

        if (Preg::isMatchAll($regex, $content, $matches)) {
            $index = count($matches[1]) -1;
            $line = $matches[1][$index];
            $content .= $line.PHP_EOL;
        }

        file_put_contents((string) $this->tmpIni, $content);
        Process::setEnv('XDEBUG_MODE', 'coverage');

        parent::restart($command);
    }
}
