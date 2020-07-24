<?php

namespace Phpactor\Extension\LanguageServer\Tests\Unit\Handler;

use Phpactor\Extension\LanguageServer\Tests\Unit\LanguageServerTestCase;

class SessionHandlerTest extends LanguageServerTestCase
{
    public function testDumpConfig(): Void
    {
        $tester = $this->createTester();
        $response = $tester->requestAndWait('phpactor/debug/config', []);
        $this->assertSuccess($response);
    }

    public function testDumpWorkspace()
    {
        $tester = $this->createTester();
        $response = $tester->requestAndWait('phpactor/debug/workspace', []);
        $this->assertSuccess($response);
    }
}
