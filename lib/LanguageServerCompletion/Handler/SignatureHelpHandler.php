<?php

namespace Phpactor\Extension\LanguageServerCompletion\Handler;

use Amp\Promise;
use LanguageServerProtocol\Position;
use LanguageServerProtocol\ServerCapabilities;
use LanguageServerProtocol\SignatureHelp;
use LanguageServerProtocol\SignatureHelpOptions;
use LanguageServerProtocol\TextDocumentIdentifier;
use Phpactor\Completion\Core\Exception\CouldNotHelpWithSignature;
use Phpactor\Completion\Core\SignatureHelper;
use Phpactor\Extension\LanguageServerCompletion\Util\PhpactorToLspSignature;
use Phpactor\LanguageServer\Core\Handler\CanRegisterCapabilities;
use Phpactor\LanguageServer\Core\Handler\Handler;
use Phpactor\LanguageServer\Core\Session\Workspace;
use Phpactor\TextDocument\ByteOffset;
use Phpactor\TextDocument\TextDocumentBuilder;

class SignatureHelpHandler implements Handler, CanRegisterCapabilities
{
    /**
     * @var Workspace
     */
    private $workspace;

    /**
     * @var SignatureHelper
     */
    private $helper;

    public function __construct(Workspace $workspace, SignatureHelper $helper)
    {
        $this->workspace = $workspace;
        $this->helper = $helper;
    }

    /**
     * {@inheritDoc}
     */
    public function methods(): array
    {
        return [
            'textDocument/signatureHelp' => 'signatureHelp'
        ];
    }

    public function signatureHelp(
        TextDocumentIdentifier $textDocument,
        Position $position
    ): Promise {
        return \Amp\call(function () use ($textDocument, $position) {
            $textDocument = $this->workspace->get($textDocument->uri);

            $languageId = $textDocument->languageId ?: 'php';

            try {
                return PhpactorToLspSignature::toLspSignatureHelp($this->helper->signatureHelp(
                    TextDocumentBuilder::create($textDocument->text)->language($languageId)->uri($textDocument->uri)->build(),
                    ByteOffset::fromInt($position->toOffset($textDocument->text))
                ));
            } catch (CouldNotHelpWithSignature $couldNotHelp) {
                return new SignatureHelp();
            }
        });
    }

    public function registerCapabiltiies(ServerCapabilities $capabilities): void
    {
        $options = new SignatureHelpOptions();
        $options->triggerCharacters = [ '(', ',' ];
        $capabilities->signatureHelpProvider = $options;
    }
}
