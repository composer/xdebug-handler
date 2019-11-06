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

use PHPUnit\Framework\TestCase;

/**
 * This class does not need to extend Helpers\BaseTestCase
 */
class ColorOptionTest extends TestCase
{
    private static $nocolor;

    /**
     * Saves the NO_COLOR environment variable
     *
     * @beforeClass
     */
    public static function beforeClass()
    {
        self::$nocolor = getenv('NO_COLOR');
    }

    /**
     * Restores the original NO_COLOR environment variable
     *
     * @afterClass
     */
    public static function afterClass()
    {
        $value = false !== self::$nocolor ? '='.self::$nocolor : '';
        putenv('NO_COLOR'.$value);
    }

    /**
     * Unsets the NO_COLOR environment variable for each test
     *
     * @before
     */
    public function setUpEnvironment()
    {
        putenv('NO_COLOR');
    }

    /**
     * Tests that a colorOption is added to the arguments
     *
     * @dataProvider neededProvider
     */
    public function testOptionNeeded($args, $colorOption, $expected)
    {
        $result = Process::addColorOption($args, $colorOption);
        $this->assertSame($expected, implode(' ', $result));
    }

    /**
     * Tests that a colorOption is not added if NO_COLOR is set
     *
     * @dataProvider neededProvider
     */
    public function testOptionNeededNoColor($args, $colorOption, $unused)
    {
        putenv('NO_COLOR=1');
        $result = Process::addColorOption($args, $colorOption);
        $this->assertSame($args, $result);
    }

    public function neededProvider()
    {
        // $args, $colorOption, $expected
        return array(
            'simple' => array(array('--option', 'param'), '--xxx', '--option param --xxx'),
            'complex' => array(array('--option', 'param'), '--xxx=yyy', '--option param --xxx=yyy'),
            'position' => array(array('--option', '--', 'param'), '--xxx', '--option --xxx -- param'),
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
        $args = array($existing, '--option', 'param');

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
        $args = array('--option', 'param');

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
        $args = array('--xxx=auto', 'param');
        $colorOption = '--xxx=always';

        $result = Process::addColorOption($args, $colorOption);
        $this->assertSame('--xxx=always param', implode(' ', $result));
    }
}
