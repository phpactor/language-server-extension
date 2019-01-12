<?php

namespace Phpactor\Extension\LanguageServer\Tests\Unit\Handler;

use Phpactor\Extension\LanguageServer\Tests\Unit\LanguageServerTestCase;

class SessionHandlerTest extends LanguageServerTestCase
{
    public function testSessionHandler()
    {
        $tester = $this->createTester();
        $tester->initialize();
        $responses = $tester->dispatch('session/dumpConfig', []);
        $tester->assertSuccess($responses);
        $this->assertCount(2, $responses);
    }
}
