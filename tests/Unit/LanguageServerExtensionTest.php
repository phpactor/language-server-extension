<?php

namespace Phpactor\Extension\LanguageServer\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Phpactor\Container\Container;
use Phpactor\Container\PhpactorContainer;
use Phpactor\Extension\Console\ConsoleExtension;
use Phpactor\Extension\LanguageServer\LanguageServerExtension;
use Phpactor\Extension\Logger\LoggingExtension;
use Phpactor\FilePathResolverExtension\FilePathResolverExtension;
use Phpactor\LanguageServer\Core\Server\LanguageServer;
use Phpactor\LanguageServer\LanguageServerBuilder;
use Phpactor\Extension\LanguageServer\Tests\Example\TestExtension;
use Phpactor\LanguageServer\Test\ServerTester;

class LanguageServerExtensionTest extends TestCase
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
        $responses = $serverTester->dispatch('test', []);
        $this->assertCount(2, $responses);

        $this->assertTrue($serverTester->assertSuccess($responses));
    }

    private function createContainer(array $params = []): Container
    {
        return PhpactorContainer::fromExtensions([
            TestExtension::class,
            ConsoleExtension::class,
            LanguageServerExtension::class,
            LoggingExtension::class,
            FilePathResolverExtension::class
        ], $params);
    }

    private function createTester(): ServerTester
    {
        $builder = $this->createContainer()->get(
            LanguageServerExtension::SERVICE_LANGUAGE_SERVER_BUILDER
        );
        
        $this->assertInstanceOf(LanguageServerBuilder::class, $builder);
        
        $serverTester = $builder->buildServerTester();

        return $serverTester;
    }
}
