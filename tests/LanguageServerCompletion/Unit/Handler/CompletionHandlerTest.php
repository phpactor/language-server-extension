<?php

namespace Phpactor\Extension\LanguageServerCompletion\Tests\Unit\Handler;

use Amp\Delayed;
use DTL\Invoke\Invoke;
use Generator;
use LanguageServerProtocol\CompletionItem;
use LanguageServerProtocol\CompletionList;
use LanguageServerProtocol\Position;
use LanguageServerProtocol\Range;
use LanguageServerProtocol\TextDocumentItem;
use LanguageServerProtocol\TextEdit;
use PHPUnit\Framework\TestCase;
use Phpactor\Completion\Core\Completor;
use Phpactor\Completion\Core\Range as PhpactorRange;
use Phpactor\Completion\Core\Suggestion;
use Phpactor\Completion\Core\TypedCompletorRegistry;
use Phpactor\Extension\LanguageServerCompletion\Handler\CompletionHandler;
use Phpactor\Extension\LanguageServerCompletion\Util\SuggestionNameFormatter;
use Phpactor\Extension\LanguageServer\Util\ClientCapabilitiesProvider;
use Phpactor\LanguageServer\Core\Session\Workspace;
use Phpactor\LanguageServer\Test\HandlerTester;
use Phpactor\TextDocument\ByteOffset;
use Phpactor\TextDocument\TextDocument;

class CompletionHandlerTest extends TestCase
{
    /**
     * @var TextDocumentItem
     */
    private $document;

    /**
     * @var Position
     */
    private $position;

    public function setUp(): void
    {
        $this->document = new TextDocumentItem();
        $this->document->uri = '/test';
        $this->document->text = 'hello';
        $this->position = new Position(1, 1);
        $this->workspace = new Workspace();

        $this->workspace->open($this->document);
    }

    public function testHandleNoSuggestions()
    {
        $tester = $this->create([]);
        $response = $tester->dispatchAndWait(
            'textDocument/completion',
            [
                'textDocument' => $this->document,
                'position' => $this->position
            ]
        );
        $this->assertInstanceOf(CompletionList::class, $response->result);
        $this->assertEquals([], $response->result->items);
    }

    public function testHandleSuggestions()
    {
        $tester = $this->create([
            Suggestion::create('hello'),
            Suggestion::create('goodbye'),
        ]);
        $response = $tester->dispatchAndWait(
            'textDocument/completion',
            [
                'textDocument' => $this->document,
                'position' => $this->position
            ]
        );
        $this->assertInstanceOf(CompletionList::class, $response->result);
        $this->assertEquals([
            self::completionItem('hello', null),
            self::completionItem('goodbye', null),
        ], $response->result->items);
    }

    public function testHandleSuggestionsWithRange()
    {
        $tester = $this->create([
            Suggestion::createWithOptions('hello', [ 'range' => PhpactorRange::fromStartAndEnd(1, 2)]),
        ]);
        $response = $tester->dispatchAndWait(
            'textDocument/completion',
            [
                'textDocument' => $this->document,
                'position' => $this->position
            ]
        );
        $this->assertEquals([
            self::completionItem('hello', null, ['textEdit' => new TextEdit(
                new Range(new Position(0, 1), new Position(0, 2)),
                'hello'
            )])
        ], $response->result->items);
    }

    public function testCancelReturnsPartialResults()
    {
        $tester = $this->create(
            array_map(function () {
                return Suggestion::createWithOptions('hello', [ 'range' => PhpactorRange::fromStartAndEnd(1, 2)]);
            }, range(0, 10000))
        );
        $response = $tester->dispatch(
            'textDocument/completion',
            [
                'textDocument' => $this->document,
                'position' => $this->position
            ]
        );
        $responses =\Amp\Promise\wait(\Amp\Promise\all([
            $response,
            \Amp\call(function () use ($tester) {
                yield new Delayed(10);
                $tester->cancel();
            })
        ]));

        $this->assertGreaterThan(1, count($responses[0]->result->items));
    }

    public function testHandleSuggestionsWithSnippets()
    {
        $tester = $this->create([
            Suggestion::createWithOptions('hello', [
                'type' => Suggestion::TYPE_METHOD,
                'label' => 'hello'
            ]),
            Suggestion::createWithOptions('goodbye', [
                'type' => Suggestion::TYPE_METHOD,
                'snippet' => 'goodbye()',
            ]),
            Suggestion::createWithOptions('$var', [
                'type' => Suggestion::TYPE_VARIABLE,
            ]),
            Suggestion::createWithOptions('hello', [
                'type' => Suggestion::TYPE_METHOD,
                'snippet' => 'hello()',
                'range' => PhpactorRange::fromStartAndEnd(1, 2),
            ]),
        ]);
        $response = $tester->dispatchAndWait(
            'textDocument/completion',
            [
                'textDocument' => $this->document,
                'position' => $this->position
            ]
        );
        $this->assertEquals([
            self::completionItem('hello', 2),
            self::completionItem('goodbye', 2, ['insertText' => 'goodbye()', 'insertTextFormat' => 2]),
            self::completionItem('var', 6),
            self::completionItem('hello', 2, [
                'insertText' => 'hello()',
                'insertTextFormat' => 2,
                'textEdit' => new TextEdit(
                    new Range(new Position(0, 1), new Position(0, 2)),
                    'hello()'
                ),
            ])
        ], $response->result->items);
    }

    public function testHandleSuggestionsWithSnippetsWhenClientDoesNotSupportIt()
    {
        $tester = $this->create([
            Suggestion::createWithOptions('hello', [
                'type' => Suggestion::TYPE_METHOD,
                'label' => 'hello'
            ]),
            Suggestion::createWithOptions('goodbye', [
                'type' => Suggestion::TYPE_METHOD,
                'snippet' => 'goodbye()',
            ]),
            Suggestion::createWithOptions('$var', [
                'type' => Suggestion::TYPE_VARIABLE,
            ]),
            Suggestion::createWithOptions('hello', [
                'type' => Suggestion::TYPE_METHOD,
                'snippet' => 'hello()',
                'range' => PhpactorRange::fromStartAndEnd(1, 2),
            ]),
        ], false);
        $response = $tester->dispatchAndWait(
            'textDocument/completion',
            [
                'textDocument' => $this->document,
                'position' => $this->position
            ]
        );
        $this->assertEquals([
            self::completionItem('hello', 2),
            self::completionItem('goodbye', 2),
            self::completionItem('var', 6),
            self::completionItem('hello', 2, [
                'textEdit' => new TextEdit(
                    new Range(new Position(0, 1), new Position(0, 2)),
                    'hello'
                ),
            ])
        ], $response->result->items);
    }

    private static function completionItem(
        string $label,
        ?int $type,
        array $data = []
    ): CompletionItem {
        return Invoke::new(CompletionItem::class, \array_merge([
            'label' => $label,
            'kind' => $type,
            'detail' => '',
            'documentation' => '',
            'insertText' => $label,
            'insertTextFormat' => 1,
        ], $data));
    }

    private function create(array $suggestions, bool $supportSnippets = true): HandlerTester
    {
        $completor = $this->createCompletor($suggestions);
        $registry = new TypedCompletorRegistry([
            'php' => $completor,
        ]);
        return new HandlerTester(new CompletionHandler(
            $this->workspace,
            $registry,
            new SuggestionNameFormatter(true),
            new ClientCapabilitiesProvider(['textDocument' => ['completion' => [
                'completionItem' => ['snippetSupport' => $supportSnippets, ],
            ]]]),
            true
        ));
    }

    private function createCompletor(array $suggestions): Completor
    {
        return new class($suggestions) implements Completor {
            private $suggestions;
            public function __construct(array $suggestions)
            {
                $this->suggestions = $suggestions;
            }

            public function complete(TextDocument $source, ByteOffset $offset): Generator
            {
                foreach ($this->suggestions as $suggestion) {
                    yield $suggestion;

                    // simulate work
                    usleep(100);
                }
            }
        };
    }
}
