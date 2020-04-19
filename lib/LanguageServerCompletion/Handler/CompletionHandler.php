<?php

namespace Phpactor\Extension\LanguageServerCompletion\Handler;

use Amp\CancellationToken;
use Amp\CancelledException;
use Amp\Delayed;
use Amp\Promise;
use LanguageServerProtocol\CompletionItem;
use LanguageServerProtocol\CompletionList;
use LanguageServerProtocol\CompletionOptions;
use LanguageServerProtocol\InsertTextFormat;
use LanguageServerProtocol\Position;
use LanguageServerProtocol\Range;
use LanguageServerProtocol\ServerCapabilities;
use LanguageServerProtocol\SignatureHelpOptions;
use LanguageServerProtocol\TextDocumentItem;
use LanguageServerProtocol\TextEdit;
use Phpactor\Completion\Core\Completor;
use Phpactor\Completion\Core\Suggestion;
use Phpactor\Completion\Core\TypedCompletorRegistry;
use Phpactor\Extension\LanguageServerCompletion\Util\PhpactorToLspCompletionType;
use Phpactor\Extension\LanguageServerCompletion\Util\SuggestionNameFormatter;
use Phpactor\Extension\LanguageServer\Helper\OffsetHelper;
use Phpactor\Extension\LanguageServer\Util\ClientCapabilitiesProvider;
use Phpactor\LanguageServer\Core\Handler\CanRegisterCapabilities;
use Phpactor\LanguageServer\Core\Handler\Handler;
use Phpactor\LanguageServer\Core\Session\Workspace;
use Phpactor\TextDocument\ByteOffset;
use Phpactor\TextDocument\TextDocumentBuilder;

class CompletionHandler implements Handler, CanRegisterCapabilities
{
    /**
     * @var Completor
     */
    private $completor;

    /**
     * @var TypedCompletorRegistry
     */
    private $registry;

    /**
     * @var bool
     */
    private $provideTextEdit;

    /**
     * @var SuggestionNameFormatter
     */
    private $suggestionNameFormatter;

    /**
     * @var Workspace
     */
    private $workspace;

    /**
     * @var ClientCapabilitiesProvider
     */
    private $clientCapabilities;

    public function __construct(
        Workspace $workspace,
        TypedCompletorRegistry $registry,
        SuggestionNameFormatter $suggestionNameFormatter,
        ClientCapabilitiesProvider $clientCapabilities,
        bool $provideTextEdit = false
    ) {
        $this->registry = $registry;
        $this->provideTextEdit = $provideTextEdit;
        $this->workspace = $workspace;
        $this->suggestionNameFormatter = $suggestionNameFormatter;
        $this->clientCapabilities = $clientCapabilities;
    }

    public function methods(): array
    {
        return [
            'textDocument/completion' => 'completion',
        ];
    }

    public function completion(TextDocumentItem $textDocument, Position $position, CancellationToken $token): Promise
    {
        return \Amp\call(function () use ($textDocument, $position, $token) {
            $textDocument = $this->workspace->get($textDocument->uri);

            $languageId = $textDocument->languageId ?: 'php';
            $suggestions = $this->registry->completorForType(
                $languageId
            )->complete(
                TextDocumentBuilder::create($textDocument->text)->language($languageId)->uri($textDocument->uri)->build(),
                ByteOffset::fromInt($position->toOffset($textDocument->text))
            );

            $completionList = new CompletionList();
            $completionList->isIncomplete = true;

            foreach ($suggestions as $suggestion) {
                $name = $this->suggestionNameFormatter->format($suggestion);
                $insertText = $name;
                $insertTextFormat = InsertTextFormat::PLAIN_TEXT;

                if ($this->shouldUseSnippet()) {
                    $insertText = $suggestion->snippet() ?: $name;
                    $insertTextFormat = $suggestion->snippet()
                        ? InsertTextFormat::SNIPPET
                        : InsertTextFormat::PLAIN_TEXT
                    ;
                }

                $completionList->items[] = new CompletionItem(
                    $name,
                    PhpactorToLspCompletionType::fromPhpactorType($suggestion->type()),
                    $suggestion->shortDescription(),
                    $suggestion->documentation(),
                    null,
                    null,
                    $insertText,
                    $this->textEdit($insertText, $suggestion, $textDocument),
                    null,
                    null,
                    null,
                    $insertTextFormat
                );

                try {
                    $token->throwIfRequested();
                } catch (CancelledException $cancellation) {
                    break;
                }
                yield new Delayed(0);
            }

            return $completionList;
        });
    }

    public function registerCapabiltiies(ServerCapabilities $capabilities): void
    {
        $capabilities->completionProvider = new CompletionOptions(false, [':', '>', '$']);
        $capabilities->signatureHelpProvider = new SignatureHelpOptions(['(', ',']);
    }

    private function textEdit(
        string $insertText,
        Suggestion $suggestion,
        TextDocumentItem $textDocument
    ): ?TextEdit {
        if (false === $this->provideTextEdit) {
            return null;
        }

        $range = $suggestion->range();

        if (!$range) {
            return null;
        }

        return new TextEdit(
            new Range(
                OffsetHelper::offsetToPosition($textDocument->text, $range->start()->toInt()),
                OffsetHelper::offsetToPosition($textDocument->text, $range->end()->toInt())
            ),
            $insertText
        );
    }

    private function shouldUseSnippet(): bool
    {
        return $this->clientCapabilities->has(ClientCapabilitiesProvider::COMPLETION_SUPPORT_SNIPPET)
            && $this->clientCapabilities->get(ClientCapabilitiesProvider::COMPLETION_SUPPORT_SNIPPET)
        ;
    }
}
