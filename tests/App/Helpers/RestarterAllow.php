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

/**
 * Restarts without xdebug only if not a help command
 *
 */

class RestarterAllow extends XdebugHandler
{
    protected function requiresRestart(bool $default): bool
    {
        $argv = is_array($_SERVER['argv'] ?? []) ? $_SERVER['argv'] : [];
        // @phpstan-ignore-next-line
        $matches = Preg::grep('/^-h$|^(?:--)?help$/', $argv);
        $required = count($matches) === 0;

        return $default && $required;
    }
}
