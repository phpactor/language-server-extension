<?php

namespace Phpactor\Extension\LanguageServer\Tests\Unit;

class LanguageServerExtensionTest extends LanguageServerTestCase
{
    public function testInitializesLanguageServer()
    {
        $serverTester = $this->createTester();
        $serverTester->initialize();
    }

    public function testLoadsTextDocuments()
    {
        $serverTester = $this->createTester();
        $responses = $serverTester->initialize();
        $serverTester->assertSuccess($responses);
    }

    public function testLoadsHandlers()
    {
        $serverTester = $this->createTester();
        $serverTester->initialize();
        $response = $serverTester->dispatchAndWait(1, 'test', []);
        $this->assertTrue($serverTester->assertSuccess($response));
    }
}
