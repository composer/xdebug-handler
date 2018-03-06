<?php
namespace Composer\XDebugHandler;

use PHPUnit\Framework\TestCase;
use Composer\XdebugHandler\Process;

/**
 * This class does not need to extend Helpers\BaseTestCase
 */
class StdinNameLegacyTest extends TestCase
{
    public function testReplacesStdin()
    {
        $args = array('php://stdin', 'asd');
        $this->assertEquals(
            array('--', 'asd'),
            Process::fixStdinName($args)
        );
    }

    public function testScriptFilenameIsLeftIntact()
    {
        $args = array('script.php', 'asd');
        $this->assertEquals($args, Process::fixStdinName($args));
    }
}
