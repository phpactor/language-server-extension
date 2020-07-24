<?php

namespace Phpactor\Extension\LanguageServer\Tests\Unit;

use Phpactor\Extension\LanguageServer\LanguageServerExtension;
use Phpactor\LanguageServerProtocol\ClientCapabilities;
use Phpactor\LanguageServerProtocol\InitializeParams;
use Phpactor\LanguageServer\Core\Rpc\NotificationMessage;
use Phpactor\LanguageServer\Core\Rpc\ResponseMessage;
use Phpactor\LanguageServer\Core\Server\Exception\ExitSession;
use Phpactor\LanguageServer\Core\Session\WorkspaceListener;

class LanguageServerExtensionTest extends LanguageServerTestCase
{
    public function testInitializesLanguageServer(): void
    {
        $serverTester = $this->createTester();
    }

    public function testLoadsTextDocuments(): void
    {
        $serverTester = $this->createTester();
        $serverTester->openTextDocument(__FILE__, (string)file_get_contents(__FILE__));
    }

    public function testLoadsHandlers(): void
    {
        $serverTester = $this->createTester();
        $response = $serverTester->requestAndWait('test', []);
        $this->assertSuccess($response);
    }

    public function testReturnsStats(): void
    {
        $serverTester = $this->createTester();
        $response = $serverTester->requestAndWait('phpactor/stats', []);
        $this->assertSuccess($response);
        $message = $serverTester->transmitter()->shift();
        self::assertNotNull($message);
        assert($message instanceof NotificationMessage);
        self::assertStringContainsString('requests: 0', $message->params['message']);
    }

    public function testExit(): void
    {
        $this->expectException(ExitSession::class);

        $serverTester = $this->createTester();
        $serverTester->requestAndWait('exit', []);
    }

    public function test(): void
    {
        $this->expectException(ExitSession::class);

        $serverTester = $this->createTester();
        $serverTester->requestAndWait('exit', []);
    }


    public function testRegistersCommands(): void
    {
        $serverTester = $this->createTester();
        $response = $serverTester->requestAndWait('workspace/executeCommand', [
            'command' => 'echo',
            'arguments' => [
                'hello',
            ],
        ]);
        $this->assertSuccess($response);
        $this->assertEquals('hello', $response->result);
    }

    public function testNullPath(): void
    {
        $this->expectException(ExitSession::class);

        $this->createTester(InitializeParams::fromArray([
            'capabilities' => [],
            'rootUri' => null,
        ]));
    }

    public function testDisablesWorkspaceListener(): void
    {
        // workspace is enabled by default
        $container = $this->createContainer();
        self::assertInstanceOf(WorkspaceListener::class, $container->get(WorkspaceListener::class));

        // if disabled it returns NULL and will not be registered
        $container = $this->createContainer([
            LanguageServerExtension::PARAM_ENABLE_WORKPACE => false,
        ]);
        self::assertNull($container->get(WorkspaceListener::class));
    }
}
