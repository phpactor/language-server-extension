<?php

namespace Phpactor\Extension\LanguageServerWorseReflection\Workspace;

use Phpactor\TextDocument\TextDocument;
use Phpactor\TextDocument\TextDocumentBuilder;
use Phpactor\TextDocument\TextDocumentUri;
use Phpactor\WorseReflection\Core\Name;
use Phpactor\WorseReflection\Core\Reflector\SourceCodeReflector;
use RuntimeException;

class WorkspaceIndex
{
    /**
     * @var SourceCodeReflector
     */
    private $reflector;

    /**
     * @var array<string, TextDocument>
     */
    private $byName = [];

    /**
     * @var array<string, TextDocument>
     */
    private $byFunction = [];

    public function __construct(SourceCodeReflector $reflector)
    {
        $this->reflector = $reflector;
    }

    public function documentForName(Name $name): ?TextDocument
    {
        if (isset($this->byName[$name->full()])) {
            return $this->byName[$name->full()];
        }

        return null;
    }

    public function index(TextDocument $textDocument): void
    {
        foreach ($this->reflector->reflectClassesIn($textDocument) as $reflectionClass) {
            $this->byName[$reflectionClass->name()->full()] = $textDocument;
        }

        foreach ($this->reflector->reflectFunctionsIn($textDocument) as $reflectionFunction) {
            $this->byName[$reflectionFunction->name()->full()] = $textDocument;
        }
    }

    public function update(TextDocumentUri $textDocumentUri, string $updatedText): void
    {
        foreach ($this->byName as $className => $textDocument) {
            if ($textDocumentUri != $textDocument->uri()) {
                continue;
            }

            $this->byName[$className] = TextDocumentBuilder::fromTextDocument($textDocument)->text($updatedText)->build();
            return;
        }

        throw new RuntimeException(sprintf(
            'Could not find document "%s"',
            $textDocumentUri->__toString()
        ));
    }

    public function remove(TextDocumentUri $textDocumentUri): void
    {
        foreach ($this->byName as $className => $textDocument) {
            if ($textDocumentUri != $textDocument->uri()) {
                continue;
            }

            unset($this->byName[$className]);
            return;
        }

        throw new RuntimeException(sprintf(
            'Could not find document "%s"',
            $textDocumentUri->__toString()
        ));
    }
}
