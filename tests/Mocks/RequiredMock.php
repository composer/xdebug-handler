<?php

/*
 * This file is part of composer/xdebug-handler.
 *
 * (c) Composer <https://github.com/composer>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Composer\XdebugHandler\Tests\Mocks;

/**
 * RequiredMock provides the runCoreMock method to set the $required property
 * and then calls the parent createAndCheck method.
 */
class RequiredMock extends CoreMock
{
    /** @var bool */
    protected static $required;

    /**
     * @return static
     */
    public static function runCoreMock(bool $loaded, bool $required): self
    {
        static::$required = $required;
        return parent::createAndCheck($loaded, null);
    }

    protected function requiresRestart(bool $default): bool
    {
        return $default && static::$required;
    }
}
