<?php

namespace Phpactor\Extension\LanguageServerReferenceFinder\Tests\Unit\Handler;

use LanguageServerProtocol\Location;
use LanguageServerProtocol\Position;
use LanguageServerProtocol\TextDocumentIdentifier;
use LanguageServerProtocol\TextDocumentItem;
use Phpactor\Extension\LanguageServerReferenceFinder\Handler\GotoDefinitionHandler;
use Phpactor\LanguageServer\Core\Server\ServerClient;
use Phpactor\LanguageServer\Core\Session\Workspace;
use Phpactor\LanguageServer\Test\HandlerTester;
use Phpactor\ReferenceFinder\DefinitionLocation;
use Phpactor\ReferenceFinder\DefinitionLocator;
use Phpactor\TestUtils\PHPUnit\TestCase;
use Phpactor\TextDocument\ByteOffset;
use Phpactor\TextDocument\TextDocumentBuilder;

class GotoDefinitionHandlerTest extends TestCase
{
    const EXAMPLE_URI = '/test';
    const EXAMPLE_TEXT = 'hello';

    /**
     * @var ObjectProphecy|DefinitionLocator
     */
    private $locator;

    /**
     * @var TextDocumentItem
     */
    private $document;

    /**
     * @var Position
     */
    private $position;

    /**
     * @var TextDocumentIdentifier
     */
    private $identifier;

    /**
     * @var Workspace
     */
    private $workspace;

    /**
     * @var ObjectProphecy
     */
    private $serverClient;

    protected function setUp(): void
    {
        $this->locator = $this->prophesize(DefinitionLocator::class);
        $this->serverClient = $this->prophesize(ServerClient::class);
        $this->workspace = new Workspace();

        $this->document = new TextDocumentItem();
        $this->document->uri = __FILE__;
        $this->document->text = self::EXAMPLE_TEXT;
        $this->workspace->open($this->document);
        $this->identifier = new TextDocumentIdentifier(__FILE__);
        $this->position = new Position(1, 1);
    }

    public function testGoesToDefinition()
    {
        $document = TextDocumentBuilder::create(self::EXAMPLE_TEXT)
            ->language('php')
            ->uri(__FILE__)
            ->build()
        ;

        $this->locator->locateDefinition(
            $document,
            ByteOffset::fromInt(7)
        )->willReturn(
            new DefinitionLocation($document->uri(), ByteOffset::fromInt(2))
        );

        $tester = new HandlerTester(new GotoDefinitionHandler(
            $this->workspace,
            $this->locator->reveal(),
        ));
        $response = $tester->dispatchAndWait('textDocument/definition', [
            'textDocument' => $this->identifier,
            'position' => $this->position,
            'client' => $this->serverClient->reveal()
        ]);
        $location = $response->result;
        $this->assertInstanceOf(Location::class, $location);
        $this->assertEquals('file://' . __FILE__, $location->uri);
        $this->assertEquals(2, $location->range->start->character);
    }
}
