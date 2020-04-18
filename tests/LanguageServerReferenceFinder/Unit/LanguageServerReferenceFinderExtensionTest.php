<?php

namespace Phpactor\Extension\LanguageServerReferenceFinder\Tests\Unit;

use LanguageServerProtocol\TextDocumentIdentifier;
use LanguageServerProtocol\TextDocumentItem;
use PHPUnit\Framework\TestCase;
use Phpactor\Container\PhpactorContainer;
use Phpactor\Extension\LanguageServerReferenceFinder\LanguageServerReferenceFinderExtension;
use Phpactor\Extension\LanguageServer\LanguageServerExtension;
use Phpactor\Extension\Logger\LoggingExtension;
use Phpactor\Extension\ReferenceFinder\ReferenceFinderExtension;
use Phpactor\FilePathResolverExtension\FilePathResolverExtension;
use Phpactor\LanguageServer\Core\Server\ResponseWatcher;
use Phpactor\LanguageServer\Core\Server\ServerClient;
use Phpactor\LanguageServer\Core\Server\Transmitter\NullMessageTransmitter;
use Phpactor\LanguageServer\LanguageServerBuilder;
use Phpactor\LanguageServer\Test\ServerTester;

class LanguageServerReferenceFinderExtensionTest extends TestCase
{
    public function testDefinition()
    {
        $tester = $this->createTester();
        $tester->initialize();
        $tester->openDocument(new TextDocumentItem(__FILE__, 'php', 84, file_get_contents(__FILE__)));

        $response = $tester->dispatchAndWait(1, 'textDocument/definition', [
            'textDocument' => new TextDocumentIdentifier(__FILE__),
            'position' => [
            ],
        ], [
            new ServerClient(new NullMessageTransmitter(), new ResponseWatcher())
        ]);
        $this->assertNull($response->result, 'Definition was not found');
    }

    public function testTypeDefinition()
    {
        $tester = $this->createTester();
        $tester->initialize();
        $tester->openDocument(new TextDocumentItem(__FILE__, 'php', 1, file_get_contents(__FILE__)));

        $response = $tester->dispatchAndWait(1, 'textDocument/typeDefinition', [
            'textDocument' => new TextDocumentIdentifier(__FILE__),
            'position' => [
            ],
        ]);
        $this->assertNull($response->result, 'Type was not found');
    }

    private function createTester(): ServerTester
    {
        $container = PhpactorContainer::fromExtensions([
            LoggingExtension::class,
            LanguageServerExtension::class,
            LanguageServerReferenceFinderExtension::class,
            ReferenceFinderExtension::class,
            FilePathResolverExtension::class,
        ]);
        
        $builder = $container->get(LanguageServerExtension::SERVICE_LANGUAGE_SERVER_BUILDER);
        $this->assertInstanceOf(LanguageServerBuilder::class, $builder);

        return $builder->buildServerTester();
    }
}
