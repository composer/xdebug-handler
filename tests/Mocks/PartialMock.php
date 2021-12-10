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
 * PartialMock provides its own restart method that simply sets the restarted
 * property to true, rather than mocking a restart.
 *
 * It can be used to test the state of the original parent process.
 */
class PartialMock extends CoreMock
{
    /** @var string[] */
    protected $command;

    /**
     * @return string[]
     */
    public function getCommand(): array
    {
        return $this->command;
    }

    public function getTmpIni(): ?string
    {
        return $this->tmpIni;
    }

    /**
     * @inheritdoc
     */
    protected function restart(array $command): void
    {
        $this->command = $command;
        $this->restarted = true;
    }
}
