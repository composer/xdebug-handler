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

use Composer\XdebugHandler\XdebugHandler;

/**
 * Restarts with phar.readonly=0 if needed, even if xdebug is not loaded
 *
 */

class RestarterIni extends XdebugHandler
{
    /** @var bool */
    private $required;

    protected function requiresRestart(bool $default): bool
    {
        $this->required = (bool) ini_get('phar.readonly');

        return $default || $this->required;

    }

    protected function restart(array $command): void
    {
        if ($this->required) {
            $content = file_get_contents((string) $this->tmpIni);
            $content .= 'phar.readonly = 0'.PHP_EOL;
            file_put_contents((string) $this->tmpIni, $content);
        }

        parent::restart($command);
    }
}
