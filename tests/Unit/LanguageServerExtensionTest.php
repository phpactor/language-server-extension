<?php

namespace Phpactor\Extension\LanguageServer\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Phpactor\Container\Container;
use Phpactor\Container\PhpactorContainer;
use Phpactor\Extension\Console\ConsoleExtension;
use Phpactor\Extension\LanguageServer\LanguageServerExtension;
use Phpactor\Extension\Logger\LoggingExtension;
use Phpactor\LanguageServer\Core\Connection\SimpleConnection;
use Phpactor\LanguageServer\Core\Server\Server;
use Phpactor\LanguageServer\LanguageServerBuilder;
use Phpactor\Extension\LanguageServer\Tests\Example\TestExtension;

class LanguageServerExtensionTest extends TestCase
{
    public function testProvidesLanguageServerBuilder()
    {
        /** @var LanguageServerBuilder $builder */
        $builder = $this->createContainer()->get(LanguageServerExtension::SERVICE_LANGUAGE_SERVER_BUILDER);
        $this->assertInstanceOf(LanguageServerBuilder::class, $builder);
        $server = $builder->build();
        $this->assertInstanceOf(Server::class, $server);
    }

    public function testLoadsHandlers()
    {
        $container = $this->createContainer();
        $builder = $container->get(
            LanguageServerExtension::SERVICE_LANGUAGE_SERVER_BUILDER
        );
        $this->assertInstanceOf(LanguageServerBuilder::class, $builder);
        $server = $builder->build();
        $this->assertInstanceOf(Server::class, $server);
    }

    private function createContainer(array $params = []): Container
    {
        return PhpactorContainer::fromExtensions([
            TestExtension::class,
            ConsoleExtension::class,
            LanguageServerExtension::class,
            LoggingExtension::class
        ], $params);
    }
}
