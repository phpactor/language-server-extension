<?php

namespace Phpactor\Extension\LanguageServerWorseReflection\Tests\Unit\Workspace;

use Phpactor\Extension\LanguageServerWorseReflection\SourceLocator\WorkspaceSourceLocator;
use Phpactor\Extension\LanguageServerWorseReflection\Workspace\WorkspaceIndex;
use Phpactor\TestUtils\PHPUnit\TestCase;
use Phpactor\TextDocument\TextDocumentBuilder;
use Phpactor\WorseReflection\Core\Exception\SourceNotFound;
use Phpactor\WorseReflection\Core\Name;

class WorkspaceSourceLocatorTest extends TestCase
{
    /**
     * @var ObjectProphecy
     */
    private $index;

    /**
     * @var WorkspaceSourceLocator
     */
    private $locator;

    protected function setUp(): void
    {
        $this->index = $this->prophesize(WorkspaceIndex::class);
        $this->locator = new WorkspaceSourceLocator($this->index->reveal());
    }

    public function testLocatesInWorkspace()
    {
        $document = TextDocumentBuilder::create('foobar')->build();
        $this->index->documentForName(Name::fromString('Bar'))->willReturn($document);

        self::assertSame($document->__toString(), $this->locator->locate(Name::fromString('Bar'))->__toString());
    }

    public function testThrowsExceptionIfCannotLocate()
    {
        $this->expectException(SourceNotFound::class);
        $document = TextDocumentBuilder::create('foobar')->build();
        $this->index->documentForName(Name::fromString('Bar'))->willReturn(null);

        $this->locator->locate(Name::fromString('Bar'));
    }
}
