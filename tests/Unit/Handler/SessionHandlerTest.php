<?php

namespace Phpactor\Extension\LanguageServer\Tests\Unit\Handler;

use Phpactor\Extension\LanguageServer\Tests\Unit\LanguageServerTestCase;

class SessionHandlerTest extends LanguageServerTestCase
{
    public function testSessionHandler()
    {
        $tester = $this->createTester();
        $tester->initialize();
        $response = $tester->dispatchAndWait(1, 'session/dumpConfig', []);
        $tester->assertSuccess($response);
    }
}
