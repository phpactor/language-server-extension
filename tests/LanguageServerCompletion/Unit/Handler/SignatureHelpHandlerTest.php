<?php

namespace Phpactor\Extension\LanguageServerCompletion\Tests\Unit\Handler;

use LanguageServerProtocol\Position;
use LanguageServerProtocol\SignatureHelp as LspSignatureHelp;
use LanguageServerProtocol\TextDocumentIdentifier;
use LanguageServerProtocol\TextDocumentItem;
use PHPUnit\Framework\TestCase;
use Phpactor\Completion\Core\SignatureHelp;
use Phpactor\Completion\Core\SignatureHelper;
use Phpactor\Extension\LanguageServerCompletion\Handler\SignatureHelpHandler;
use Phpactor\LanguageServer\Core\Session\Workspace;
use Phpactor\LanguageServer\Test\HandlerTester;
use Phpactor\TextDocument\ByteOffset;
use Phpactor\TextDocument\TextDocument;

class SignatureHelpHandlerTest extends TestCase
{
    const IDENTIFIER = '/test';

    /**
     * @var TextDocumentItem
     */
    private $document;

    /**
     * @var Position
     */
    private $position;

    /**
     * @var Workspace
     */
    private $workspace;

    public function setUp(): void
    {
        $this->document = new TextDocumentItem();
        $this->document->uri = self::IDENTIFIER;
        $this->document->text = 'hello';
        $this->position = new Position(1, 1);
        $this->workspace = new Workspace();

        $this->workspace->open($this->document);
    }

    public function testHandleHelpers()
    {
        $tester = $this->create([]);
        $response = $tester->dispatchAndWait(
            'textDocument/signatureHelp',
            [
                'textDocument' => new TextDocumentIdentifier(self::IDENTIFIER),
                'position' => $this->position
            ]
        );
        $list = $response->result;
        $this->assertInstanceOf(LspSignatureHelp::class, $list);
    }

    private function create(array $suggestions): HandlerTester
    {
        return new HandlerTester(new SignatureHelpHandler(
            $this->workspace,
            $this->createHelper(),
            true
        ));
    }

    private function createHelper()
    {
        return new class() implements SignatureHelper {
            public function signatureHelp(TextDocument $textDocument, ByteOffset $offset): SignatureHelp
            {
                $help = new SignatureHelp([], 0);
                return $help;
            }
        };
    }
}
