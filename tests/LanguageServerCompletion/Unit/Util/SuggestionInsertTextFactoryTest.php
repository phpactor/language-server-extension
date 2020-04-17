<?php

namespace Phpactor\Extension\LanguageServerCompletion\Tests\Unit\Util;

use PHPUnit\Framework\TestCase;
use Phpactor\Completion\Core\Suggestion;
use Phpactor\Extension\LanguageServerCompletion\Protocol\InsertText;
use Phpactor\Extension\LanguageServerCompletion\Util\SuggestionInsertTextFactory;

final class SuggestionInsertTextFactoryTest extends TestCase
{
    const TYPE_SNIPPET = 2;
    const TYPE_PLAIN_TEXT = 1;

    /**
     * @var SuggestionInsertTextFormatter
     */
    private $factory;

    protected function setUp(): void
    {
        $this->factory = new SuggestionInsertTextFactory();
    }

    /**
     * @dataProvider provideSuggestionsToFormat
     */
    public function testFormat(
        Suggestion $suggestion,
        ?InsertText $expectedInsertText
    ): void {
        $insertText = $this->factory->createFrom($suggestion);

        $this->assertEquals($expectedInsertText, $insertText);
    }

    public function provideSuggestionsToFormat(): iterable
    {
        yield 'Variable' => [
            Suggestion::createWithOptions('variable', ['type' => Suggestion::TYPE_VARIABLE]),
            new InsertText(null)
        ];

        yield 'Field' => [
            Suggestion::createWithOptions('field', ['type' => Suggestion::TYPE_FIELD]),
            new InsertText(null)
        ];

        yield 'Class' => [
            Suggestion::createWithOptions('class', ['type' => Suggestion::TYPE_CLASS]),
            new InsertText(null)
        ];

        yield 'Interface' => [
            Suggestion::createWithOptions('interface', ['type' => Suggestion::TYPE_INTERFACE]),
            new InsertText(null)
        ];

        yield 'Module' => [
            Suggestion::createWithOptions('module', ['type' => Suggestion::TYPE_MODULE]),
            new InsertText(null)
        ];

        yield 'Property' => [
            Suggestion::createWithOptions('property', ['type' => Suggestion::TYPE_PROPERTY]),
            new InsertText(null)
        ];

        yield 'Unit' => [
            Suggestion::createWithOptions('unit', ['type' => Suggestion::TYPE_UNIT]),
            new InsertText(null)
        ];

        yield 'Value' => [
            Suggestion::createWithOptions('value', ['type' => Suggestion::TYPE_VALUE]),
            new InsertText(null)
        ];

        yield 'Enum' => [
            Suggestion::createWithOptions('enum', ['type' => Suggestion::TYPE_ENUM]),
            new InsertText(null)
        ];

        yield 'Keyword' => [
            Suggestion::createWithOptions('keyword', ['type' => Suggestion::TYPE_KEYWORD]),
            new InsertText(null)
        ];

        yield 'Snippet' => [
            Suggestion::createWithOptions('snippet', ['type' => Suggestion::TYPE_SNIPPET]),
            new InsertText(null)
        ];

        yield 'Color' => [
            Suggestion::createWithOptions('color', ['type' => Suggestion::TYPE_COLOR]),
            new InsertText(null)
        ];

        yield 'File' => [
            Suggestion::createWithOptions('file', ['type' => Suggestion::TYPE_FILE]),
            new InsertText(null)
        ];

        yield 'Reference' => [
            Suggestion::createWithOptions('reference', ['type' => Suggestion::TYPE_REFERENCE]),
            new InsertText(null)
        ];

        yield 'Constant' => [
            Suggestion::createWithOptions('constant', ['type' => Suggestion::TYPE_CONSTANT]),
            new InsertText(null)
        ];

        yield 'Function without parameters' => [
            Suggestion::createWithOptions('func', [
                'type' => Suggestion::TYPE_FUNCTION,
                'short_description' => 'func(): <unknown>'
            ]),
            InsertText::plainText('func()')
        ];

        yield 'Function with mandatory parameters' => [
            Suggestion::createWithOptions('func', [
                'type' => Suggestion::TYPE_FUNCTION,
                'short_description' => 'func(Test $test, ?int $i): <unknown>'
            ]),
            InsertText::snippet('func(${1:\$test}, ${2:\$i})${0}')
        ];

        yield 'Function with mandatory and optional parameters' => [
            Suggestion::createWithOptions('func', [
                'type' => Suggestion::TYPE_FUNCTION,
                'short_description' => 'func(Test $test, ?int $i = null): <unknown>'
            ]),
            InsertText::snippet('func(${1:\$test})${0}')
        ];

        yield 'Function with only optional parameters' => [
            Suggestion::createWithOptions('func', [
                'type' => Suggestion::TYPE_FUNCTION,
                'short_description' => 'func(?Test $test = null, int $i = 1): <unknown>'
            ]),
            InsertText::snippet('func(${1})${0}')
        ];

        yield 'Method without parameters' => [
            Suggestion::createWithOptions('method', [
                'type' => Suggestion::TYPE_METHOD,
                'short_description' => 'pri method(): <unknown>'
            ]),
            InsertText::plainText('method()')
        ];

        yield 'Method with mandatory parameters' => [
            Suggestion::createWithOptions('method', [
                'type' => Suggestion::TYPE_METHOD,
                'short_description' => 'pri method(Test $test, ?int $i): <unknown>'
            ]),
            InsertText::snippet('method(${1:\$test}, ${2:\$i})${0}')
        ];

        yield 'Method with mandatory and optional parameters' => [
            Suggestion::createWithOptions('method', [
                'type' => Suggestion::TYPE_METHOD,
                'short_description' => 'pri method(Test $test, ?int $i = null): <unknown>'
            ]),
            InsertText::snippet('method(${1:\$test})${0}')
        ];

        yield 'Method with only optional parameters' => [
            Suggestion::createWithOptions('method', [
                'type' => Suggestion::TYPE_METHOD,
                'short_description' => 'pri method(?Test $test = null, int $i = 1): <unknown>'
            ]),
            InsertText::snippet('method(${1})${0}')
        ];

        yield 'Constructor without parameters' => [
            Suggestion::createWithOptions('constructor', [
                'type' => Suggestion::TYPE_CONSTRUCTOR,
                'short_description' => 'Foo\\Bar(): <unknown>'
            ]),
            InsertText::plainText('Bar()')
        ];

        yield 'Constructor with mandatory parameters' => [
            Suggestion::createWithOptions('Bar', [
                'type' => Suggestion::TYPE_CONSTRUCTOR,
                'short_description' => 'Foo\\Bar(Test $test, ?int $i): <unknown>'
            ]),
            InsertText::snippet('Bar(${1:\$test}, ${2:\$i})${0}')
        ];

        yield 'Constructor with mandatory and optional parameters' => [
            Suggestion::createWithOptions('Bar', [
                'type' => Suggestion::TYPE_CONSTRUCTOR,
                'short_description' => 'Foo\\Bar(Test $test, ?int $i = null): <unknown>'
            ]),
            InsertText::snippet('Bar(${1:\$test})${0}')
        ];

        yield 'Constructor with only optional parameters' => [
            Suggestion::createWithOptions('Bar', [
                'type' => Suggestion::TYPE_CONSTRUCTOR,
                'short_description' => 'Foo\\Bar(?Test $test = null, int $i = 1): <unknown>'
            ]),
            InsertText::snippet('Bar(${1})${0}')
        ];
    }
}
