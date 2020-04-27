<?php

namespace Phpactor\Extension\LanguageServerHover\Tests\Unit\Handler;

use LanguageServerProtocol\Hover;
use LanguageServerProtocol\MarkupContent;
use LanguageServerProtocol\TextDocumentIdentifier;
use LanguageServerProtocol\TextDocumentItem;
use Phpactor\Extension\LanguageServerCompletion\Tests\IntegrationTestCase;
use Phpactor\Extension\LanguageServer\Helper\OffsetHelper;
use Phpactor\TestUtils\ExtractOffset;

class HoverHandlerTest extends IntegrationTestCase
{
    const PATH = 'file:///hello';

    /**
     * @dataProvider provideHover
     */
    public function testHover(string $test, string $expected)
    {
        [ $text, $offset ] = ExtractOffset::fromSource($test);

        $tester = $this->createTester();
        $tester->initialize();
        $item = new TextDocumentItem(self::PATH, 'php', 1, $text);
        $tester->openDocument($item);
        $response = $tester->dispatchAndWait(1, 'textDocument/hover', [
            'textDocument' => new TextDocumentIdentifier(self::PATH),
            'position' => OffsetHelper::offsetToPosition($text, $offset)
        ]);
        $tester->assertSuccess($response);
        $result = $response->result;
        $this->assertInstanceOf(Hover::class, $result);
        $this->assertEquals(new MarkupContent('markdown', $expected), $result->contents);
    }

    public function provideHover()
    {
        yield 'var' => [
            '<?php $foo = "foo"; $f<>oo;',
            'string'
        ];
    }
}
