<?php

namespace Phpactor\Extension\LanguageServer\Tests\Unit\Handler;

use Phpactor\Extension\LanguageServer\Tests\Unit\LanguageServerTestCase;

class SessionHandlerTest extends LanguageServerTestCase
{
    public function testDumpConfig()
    {
        $tester = $this->createTester();
        $tester->initialize();
        $response = $tester->dispatchAndWait(1, 'session/dumpConfig');
        $tester->assertSuccess($response);
    }

    public function testDumpWorkspace()
    {
        $tester = $this->createTester();
        $tester->initialize();
        $response = $tester->dispatchAndWait(1, 'session/dumpWorkspace');
        $tester->assertSuccess($response);
    }
}
