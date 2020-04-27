<?php

namespace Phpactor\Extension\LanguageServerHover\Tests\Unit\Handler;

use LanguageServerProtocol\Hover;
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
    public function testHover(string $test)
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
    }

    public function provideHover()
    {
        yield 'var' => [
            '<?php $foo = "foo"; $f<>oo;',
        ];

        yield 'poperty' => [
            '<?php class A { private $<>b; }',
        ];

        yield 'method' => [
            '<?php class A { private function f<>oo():string {} }',
        ];

        yield 'method with documentation' => [
            <<<'EOT'
<?php 

class A { 
    /** 
     * This is a method 
     */
    private function f<>oo():string {} 
}
EOT
            ,
        ];

        yield 'class' => [
            '<?php cl<>ass A { } }',
            'A'
        ];
    }
}
