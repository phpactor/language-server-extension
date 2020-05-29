<?php

namespace Phpactor\Extension\LanguageServer\Tests\Unit;

use Phpactor\Extension\LanguageServer\LanguageServerExtension;
use Phpactor\LanguageServer\Core\Server\Exception\ExitSession;
use Phpactor\LanguageServer\Core\Session\WorkspaceListener;

class LanguageServerExtensionTest extends LanguageServerTestCase
{
    public function testInitializesLanguageServer(): void
    {
        $serverTester = $this->createTester();
        $serverTester->initialize();
    }

    public function testLoadsTextDocuments(): void
    {
        $serverTester = $this->createTester();
        $responses = $serverTester->initialize();
        $serverTester->assertSuccess($responses);
    }

    public function testLoadsHandlers(): void
    {
        $serverTester = $this->createTester();
        $serverTester->initialize();
        $response = $serverTester->dispatchAndWait(1, 'test', []);
        $this->assertTrue($serverTester->assertSuccess($response));
    }

    public function testRegistersCommands(): void
    {
        $serverTester = $this->createTester();
        $serverTester->initialize();
        $response = $serverTester->dispatchAndWait(1, 'workspace/executeCommand', [
            'command' => 'echo',
            'arguments' => [
                'hello',
            ],
        ]);
        $this->assertTrue($serverTester->assertSuccess($response));
        $this->assertEquals('hello', $response->result);
    }

    public function testNullPath(): void
    {
        $this->expectException(ExitSession::class);
        $serverTester = $this->createTester();
        $serverTester->dispatchAndWait(1, 'initialize', [
            'rootUri' => null,
        ]);
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
