<?php

namespace Phpactor\Extension\LanguageServerWorseReflection\Workspace;

use Phpactor\TextDocument\TextDocument;
use Phpactor\TextDocument\TextDocumentUri;
use Phpactor\WorseReflection\Core\Name;
use Phpactor\WorseReflection\Core\Reflection\ReflectionClass;

class WorkspaceIndex
{
    /**
     * @var SourceCodeReflector
     */
    private $reflector;

    private $byClass = [];
    private $byFunction = [];

    public function __construct(SourceCodeReflector $reflector)
    {
        $this->reflector = $reflector;
    }

    public function hasClass(Name $name): bool
    {
    }

    public function index(TextDocument $textDocument): void
    {
    }

    public function update(TextDocumentUri $textDocumentUri): void
    {
    }

    public function remove(TextDocumentUri $textDocumentUri): void
    {
    }
}
