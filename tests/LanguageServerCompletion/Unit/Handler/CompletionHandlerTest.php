<?php

namespace Phpactor\Extension\LanguageServerCompletion\Tests\Unit\Handler;

use Amp\Delayed;
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
use Phpactor\LanguageServer\Core\Session\Workspace;
use Phpactor\LanguageServer\Test\HandlerTester;
use Phpactor\TextDocument\ByteOffset;
use Phpactor\TextDocument\TextDocument;
use Prophecy\PhpUnit\ProphecyTrait;

class CompletionHandlerTest extends TestCase
{
    use ProphecyTrait;

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
            new CompletionItem('hello', null, null, null, null, null, 'hello', null, null, null, null, 1),
            new CompletionItem('goodbye', null, null, null, null, null, 'goodbye', null, null, null, null, 1),
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
            new CompletionItem('hello', null, '', null, null, null, 'hello', new TextEdit(
                new Range(new Position(0, 1), new Position(0, 2)),
                'hello'
            ), null, null, null, 1),
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
        ]);
        $response = $tester->dispatchAndWait(
            'textDocument/completion',
            [
                'textDocument' => $this->document,
                'position' => $this->position
            ]
        );
        $this->assertEquals([
            new CompletionItem('hello', 2, '', '', null, null, 'hello', null, null, null, null, 1),
            new CompletionItem('goodbye', 2, '', '', null, null, 'goodbye()', null, null, null, null, 2),
        ], $response->result->items);
    }

    private function create(array $suggestions): HandlerTester
    {
        $completor = $this->createCompletor($suggestions);
        $registry = new TypedCompletorRegistry([
            'php' => $completor,
        ]);
        return new HandlerTester(new CompletionHandler(
            $this->workspace,
            $registry,
            new SuggestionNameFormatter(),
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
