<?php

namespace Phpactor\Extension\LanguageServer\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Phpactor\Container\Container;
use Phpactor\Container\PhpactorContainer;
use Phpactor\Extension\Console\ConsoleExtension;
use Phpactor\Extension\LanguageServer\LanguageServerExtension;
use Phpactor\Extension\Logger\LoggingExtension;
use Phpactor\LanguageServer\Core\Connection\SimpleConnection;
use Phpactor\LanguageServer\Core\IO\BufferIO;
use Phpactor\LanguageServer\Core\Server;
use Phpactor\LanguageServer\LanguageServerBuilder;

class LanguageServerExtensionTest extends TestCase
{
    public function testProvidesLanguageServerBuilder()
    {
        /** @var LanguageServerBuilder $builder */
        $builder = $this->createContainer()->get(LanguageServerExtension::SERVICE_LANGUAGE_SERVER_BUILDER);
        $this->assertInstanceOf(LanguageServerBuilder::class, $builder);
        $io = new BufferIO();
        $builder->withConnection(new SimpleConnection($io));
        $server = $builder->build();
        $this->assertInstanceOf(Server::class, $server);
    }

    private function createContainer(array $params = []): Container
    {
        return PhpactorContainer::fromExtensions([
            ConsoleExtension::class,
            LanguageServerExtension::class,
            LoggingExtension::class
        ], $params);
    }
}
