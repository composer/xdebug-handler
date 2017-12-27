<?php

/*
 * This file is part of composer/xdebug-handler.
 *
 * (c) Composer <https://github.com/composer>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Composer\XdebugHandler\Mocks;

/**
 * IniFileMock provides its own restart method that sets its restartIni property
 * to the location of the tmpIni file.
 *
 * It is used to test the existence and contents of this file.
 */
class IniFileMock extends PartialMock
{
    public $restartIni;

    protected function restart($command)
    {
        $this->restartIni = $this->getTmpIni();
        parent::restart($command);
    }
}
