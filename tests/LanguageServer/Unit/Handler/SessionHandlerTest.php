<?php

namespace Phpactor\Extension\LanguageServer\Tests\Unit\Handler;

use Phpactor\Extension\LanguageServer\Tests\Unit\LanguageServerTestCase;
use Phpactor\LanguageServer\Core\Server\Transmitter\NullMessageTransmitter;

class SessionHandlerTest extends LanguageServerTestCase
{
    public function testSessionHandler()
    {
        $tester = $this->createTester();
        $tester->initialize();
        $response = $tester->dispatchAndWait(1, 'session/dumpConfig', [
            '_transmitter' => new NullMessageTransmitter()
        ]);
        $tester->assertSuccess($response);
    }
}
