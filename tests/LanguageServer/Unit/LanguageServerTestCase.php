<?php

namespace Phpactor\Extension\LanguageServer\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Phpactor\Container\Container;
use Phpactor\Container\PhpactorContainer;
use Phpactor\Extension\Console\ConsoleExtension;
use Phpactor\Extension\LanguageServer\LanguageServerExtension;
use Phpactor\Extension\LanguageServer\Tests\Example\TestExtension;
use Phpactor\Extension\Logger\LoggingExtension;
use Phpactor\FilePathResolverExtension\FilePathResolverExtension;
use Phpactor\LanguageServerProtocol\ClientCapabilities;
use Phpactor\LanguageServerProtocol\InitializeParams;
use Phpactor\LanguageServer\LanguageServerBuilder;
use Phpactor\LanguageServer\Test\LanguageServerTester;
use Phpactor\LanguageServer\Test\ProtocolFactory;
use Phpactor\LanguageServer\Test\ServerTester;
use Phpactor\TestUtils\Workspace;

class LanguageServerTestCase extends TestCase
{
    protected function workspace(): Workspace
    {
        return Workspace::create(__DIR__ . '/../../Workspace');
    }

    protected function createContainer(array $params = []): Container
    {
        return PhpactorContainer::fromExtensions([
            TestExtension::class,
            ConsoleExtension::class,
            LanguageServerExtension::class,
            LoggingExtension::class,
            FilePathResolverExtension::class
        ], $params);
    }

    protected function createTester(): LanguageServerTester
    {
        $builder = $this->createContainer()->get(
            LanguageServerBuilder::class
        );
        
        $this->assertInstanceOf(LanguageServerBuilder::class, $builder);
        
        return $builder->tester(ProtocolFactory::initializeParams($this->workspace()->path('/')));
    }
}
