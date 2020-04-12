<?php

namespace Phpactor\Extension\LanguageServerCompletion\Tests\Unit;

use LanguageServerProtocol\CompletionList;
use LanguageServerProtocol\Position;
use LanguageServerProtocol\SignatureHelp;
use LanguageServerProtocol\TextDocumentIdentifier;
use LanguageServerProtocol\TextDocumentItem;
use Phpactor\Extension\LanguageServerCompletion\Tests\IntegrationTestCase;
use Phpactor\LanguageServer\Core\Rpc\ResponseMessage;

class LanguageServerCompletionExtensionTest extends IntegrationTestCase
{
    public function testComplete()
    {
        $tester = $this->createTester();
        $tester->initialize();

        $document = new TextDocumentItem();
        $document->uri = '/test';
        $document->text = 'hello';
        $position = new Position(1, 1);
        $tester->openDocument($document);

        $response = $tester->dispatchAndWait(1, 'textDocument/completion', [
            'textDocument' => $document,
            'position' => $position,
        ]);

        $this->assertInstanceOf(ResponseMessage::class, $response);
        $this->assertNull($response->error);
        $this->assertInstanceOf(CompletionList::class, $response->result);
    }

    public function testSignatureProvider()
    {
        $tester = $this->createTester();
        $tester->initialize();

        $document = new TextDocumentItem();
        $document->uri = '/test';
        $document->text = 'hello';
        $position = new Position(1, 1);
        $tester->openDocument($document);
        $identifier = new TextDocumentIdentifier($document->uri);

        $response = $tester->dispatchAndWait(1, 'textDocument/signatureHelp', [
            'textDocument' => $identifier,
            'position' => $position,
        ]);

        $this->assertInstanceOf(ResponseMessage::class, $response);
        $this->assertNull($response->error);
        $this->assertInstanceOf(SignatureHelp::class, $response->result);
    }
}
