<?php

namespace Phpactor\Extension\LanguageServerWorseReflection\Workspace;

use Phpactor\TextDocument\TextDocument;
use Phpactor\TextDocument\TextDocumentBuilder;
use Phpactor\TextDocument\TextDocumentUri;
use Phpactor\WorseReflection\Core\Name;
use Phpactor\WorseReflection\Core\Reflection\ReflectionClass;
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
    private $byClass = [];

    /**
     * @var array<string, TextDocument>
     */
    private $byFunction = [];

    public function __construct(SourceCodeReflector $reflector)
    {
        $this->reflector = $reflector;
    }

    public function documentForClass(Name $name): ?TextDocument
    {
        if (isset($this->byClass[$name->full()])) {
            return $this->byClass[$name->full()];
        }

        return null;
    }

    public function index(TextDocument $textDocument): void
    {
        foreach ($this->reflector->reflectClassesIn($textDocument) as $reflectionClass) {
            $this->byClass[$reflectionClass->name()->full()] = $textDocument;
        }
    }

    public function update(TextDocumentUri $textDocumentUri, string $updatedText): void
    {
        foreach ($this->byClass as $className => $textDocument) {
            if ($textDocumentUri != $textDocument->uri()) {
                continue;
            }

            $this->byClass[$className] = TextDocumentBuilder::fromTextDocument($textDocument)->text($updatedText)->build();
            return;
        }

        throw new RuntimeException(sprintf(
            'Could not find document "%s"',
            $textDocumentUri->__toString()
        ));
    }

    public function remove(TextDocumentUri $textDocumentUri): void
    {
        foreach ($this->byClass as $className => $textDocument) {
            if ($textDocumentUri != $textDocument->uri()) {
                continue;
            }

            unset($this->byClass[$className]);
            return;
        }

        throw new RuntimeException(sprintf(
            'Could not find document "%s"',
            $textDocumentUri->__toString()
        ));
    }
}
