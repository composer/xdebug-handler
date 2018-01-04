<?php

/*
 * This file is part of composer/xdebug-handler.
 *
 * (c) Composer <https://github.com/composer>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Composer\XdebugHandler;

use Composer\XdebugHandler\Process;
use PHPUnit\Framework\TestCase;

/**
 * This class does not need to extend Helpers\BaseTestCase
 */
class ColorOptionTest extends TestCase
{
    /**
     * Tests that a colorOption is added to the arguments
     *
     * @dataProvider neededProvider
     */
    public function testOptionNeeded($colorOption)
    {
        $args = array('script.php', 'param');

        $result = Process::addColorOption($args, $colorOption);
        $this->assertContains($colorOption, $result);
    }

    public function neededProvider()
    {
        // $colorOption
        return array(
            'simple' => array('--xxx'),
            'complex' => array('--xxx=yyy'),
        );
    }

    /**
     * Tests that a colorOption is not added to the arguments because it matches
     * an existing argument.
     *
     * @dataProvider notNeededProvider
     */
    public function testOptionNotNeeded($existing, $colorOption)
    {
        $args = array('script.php', 'param');
        $args[] = $existing;

        $result = Process::addColorOption($args, $colorOption);
        $this->assertContains($existing, $result);
        $this->assertNotContains($colorOption, $result);
    }

    public function notNeededProvider()
    {
        // $existing, $colorOption
        return array(
            'simple' => array('--no-xxx', '--xxx'),
            'complex' => array('--xxx=zzz', '--xxx=yyy'),
        );
    }

    /**
     * Tests that a colorOption is not added to the arguments because it does
     * not match the required format.
     *
     * @dataProvider notMatchedProvider
     */
    public function testOptionNotMatched($colorOption)
    {
        $args = array('script.php', 'param');

        $result = Process::addColorOption($args, $colorOption);
        $this->assertNotContains($colorOption, $result);
    }

    public function notMatchedProvider()
    {
        // $colorOption
        return array(
            'simple1' => array('xxx'),
            'simple2' => array('-xxx'),
            'complex1' => array('xxx=yyy'),
            'complex2' => array('-xxx=yyy'),
        );
    }

    /**
     * Tests that a colorOption matching xxx=auto is replaced.
     */
    public function testOptionReplaced()
    {
        $args = array('script.php', 'param');
        $existing = '--xxx=auto';
        $colorOption = '--xxx=always';
        $args[] = $existing;

        $result = Process::addColorOption($args, $colorOption);
        $this->assertContains($colorOption, $result);
        $this->assertNotContains($existing, $result);
    }
}
