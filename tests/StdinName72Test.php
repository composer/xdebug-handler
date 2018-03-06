<?php
namespace Composer\XDebugHandler;

use PHPUnit\Framework\TestCase;
use Composer\XdebugHandler\Process;

/**
 * This class does not need to extend Helpers\BaseTestCase
 */
class StdinName72Test extends TestCase
{
    public function testReplacesStandardInputCode()
    {
        $args = ['Standard input code', 'asd'];
        $this->assertEquals(
            ['--', 'asd'],
            Process::fixStdinName($args)
        );
    }

    public function testScriptFilenameIsLeftIntact()
    {
        $args = ['script.php', 'asd'];
        $this->assertEquals($args, Process::fixStdinName($args));
    }
}
